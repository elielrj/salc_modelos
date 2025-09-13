<?php
// index.php — tudo em um arquivo (tabs Bootstrap)
// Guia "Itens de Pregão" integrada com a consulta da API (UASG 160517) + DEDUP

date_default_timezone_set('America/Sao_Paulo');

/* ====== BLOCO PHP da guia Itens de Pregão ====== */
const ITENS_BASE_URL  = 'https://dadosabertos.compras.gov.br/modulo-arp/2_consultarARPItem';
const ITENS_UASG      = 160517;
const ITENS_PAGE_SIZE = 50;
const ITENS_REQUEST_DELAY_MS = 200;
const ITENS_MAX_RETRIES  = 6;
const ITENS_BASE_BACKOFF = 1.0;

$__tz      = new DateTimeZone('America/Sao_Paulo');
$__today   = new DateTime('now', $__tz);
$ITENS_VIG_MIN = (clone $__today)->modify('-365 days')->format('Y-m-d');
$ITENS_VIG_MAX = (clone $__today)->modify('+365 days')->format('Y-m-d');

$itens_paramsBase = [
    'tamanhoPagina'             => ITENS_PAGE_SIZE,
    'codigoUnidadeGerenciadora' => ITENS_UASG,
    'dataVigenciaInicialMin'    => $ITENS_VIG_MIN,
    'dataVigenciaInicialMax'    => $ITENS_VIG_MAX,
];

function _itens_get_json(string $url, array $query, bool $useCache = true, int $cacheTtlSec = 600): array
{
    $qs   = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    $full = $url . '?' . $qs;

    if ($useCache) {
        $cacheKey = sys_get_temp_dir() . '/arp_cache_' . sha1($full) . '.json';
        if (is_file($cacheKey) && (time() - filemtime($cacheKey) <= $cacheTtlSec)) {
            $json = json_decode((string)file_get_contents($cacheKey), true);
            if (is_array($json)) return $json;
        }
    }

    $attempt = 0;
    while (true) {
        $attempt++;
        $ch = curl_init($full);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'User-Agent: pregao-uasg-demo/2.5 (+retry/backoff)'
            ]
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($body !== false && $code >= 200 && $code < 300) {
            $json = json_decode($body, true);
            if (!is_array($json)) {
                return ['__error' => 'JSON inválido', '__debug' => $body];
            }
            if ($useCache) @file_put_contents($cacheKey, $body);
            return $json;
        }

        if ($code === 429 || ($code >= 500 && $code <= 599)) {
            $waitSec = null;
            if ($body && preg_match('/Try again in\s+(\d+)\s+seconds/i', (string)$body, $m)) {
                $waitSec = (int)$m[1];
            }
            if ($waitSec === null) {
                $jitter = mt_rand(100, 600) / 1000.0;
                $waitSec = (int)round(pow(2, min($attempt - 1, 5)) * ITENS_BASE_BACKOFF + $jitter);
            }
            if ($attempt <= ITENS_MAX_RETRIES) {
                usleep(max(1, (int)($waitSec * 1000000)));
                continue;
            }
        }

        return ['__error' => "HTTP $code", '__debug' => $body ?: $err];
    }
}

function _itens_sicaf_label($v): array
{
    $val = is_string($v) ? trim(mb_strtolower($v)) : $v;
    $truthy = ['true', '1', 'regular', 'ok', 'habilitado', 'ativo'];
    $falsy  = ['false', '0', 'restricao', 'restrição', 'irregular', 'inativo', 'bloqueado'];
    $isTrue  = is_bool($val) ? $val === true  : (is_numeric($val) ? ((int)$val === 1) : in_array($val, $truthy, true));
    $isFalse = is_bool($val) ? $val === false : (is_numeric($val) ? ((int)$val === 0) : in_array($val, $falsy, true));
    if ($isTrue)  return ['Regular', 'sicaf-ok'];
    if ($isFalse) return ['Restrição', 'sicaf-bad'];
    return [is_scalar($v) ? (string)$v : '—', 'sicaf-unk'];
}
function _itens_h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
function _itens_fmt_cnpj($v): string
{
    $d = preg_replace('/\D+/', '', (string)$v);
    if (strlen($d) === 14) return substr($d, 0, 2) . '.' . substr($d, 2, 3) . '.' . substr($d, 5, 3) . '/' . substr($d, 8, 4) . '-' . substr($d, 12, 2);
    return (string)$v;
}
function _itens_fmt_date_br($date): string
{
    if (!$date) return '—';
    $d = DateTime::createFromFormat('Y-m-d', substr($date, 0, 10));
    return $d ? $d->format('d/m/Y') : $date;
}

/* ===== Paginação com DEDUP ===== */
$itens_lista = [];
$itens_seen  = []; // chave: compra|ano|item
$__pagina = 1;
while (true) {
    $resp = _itens_get_json(ITENS_BASE_URL, array_merge($itens_paramsBase, ['pagina' => $__pagina]));
    if (isset($resp['__error'])) {
        $itens_err = $resp;
        break;
    }

    $lote = $resp['resultado'] ?? [];
    if (!is_array($lote) || !count($lote)) break;

    foreach ($lote as $rec) {
        $k = ($rec['numeroCompra'] ?? '') . '|' . ($rec['anoCompra'] ?? '') . '|' . ($rec['numeroItem'] ?? '');
        if (!isset($itens_seen[$k])) {
            $itens_lista[] = $rec;
            $itens_seen[$k] = true;
        }
    }

    $pagRest = $resp['paginasRestantes'] ?? null;
    if ($pagRest !== null && (int)$pagRest <= 0) break;

    $__pagina++;
    if ($__pagina > 2000) break;

    if (ITENS_REQUEST_DELAY_MS > 0) usleep(ITENS_REQUEST_DELAY_MS * 1000);
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <title>Modelos - NLLC 14.133/21</title>
    <!-- Ícone oficial gov.br/compras -->
    <link rel="icon" href="https://www.gov.br/compras/pt-br/favicon.ico" type="image/x-icon">

    <!-- Estilos apenas para a guia Itens (prefixados por #itens) -->
    <style>
        #itens {
            --fg: #111;
            --muted: #666;
            --line: #e7e7e7;
            --bg: #fff;
            --ok: #107c10;
            --bad: #c62828;
            --hl: #f7faff;
        }

        #itens h3 {
            margin-bottom: .75rem;
        }

        #itens .meta {
            color: var(--muted);
            margin-bottom: .75rem;
        }

        #itens table {
            border-collapse: collapse;
            width: 100%;
        }

        #itens th,
        #itens td {
            border: 1px solid var(--line);
            padding: 8px;
            text-align: left;
            vertical-align: middle;
            /* centraliza na vertical   */
        }


        #itens th {
            background: #f7f7f7;
            position: sticky;
            top: 0;
            cursor: pointer;
            user-select: none;
        }

        #itens th.noclick {
            cursor: default;
        }

        #itens .right {
            text-align: right;
            white-space: nowrap;
        }

        #itens .small {
            font-size: 12px;
            color: var(--muted);
        }

        #itens .nowrap {
            white-space: nowrap;
        }

        #itens .sicaf-ok {
            color: var(--ok);
            font-weight: 600;
        }

        #itens .sicaf-bad {
            color: var(--bad);
            font-weight: 700;
        }

        #itens .sicaf-unk {
            color: var(--muted);
        }

        #itens .selrow {
            background: var(--hl);
        }

        #itens .btn-mini {
            display: inline-block;
            padding: 6px 10px;
            border: 1px solid #ccc;
            background: #fff;
            cursor: pointer;
            border-radius: 6px;
        }

        #itens .btn-mini:hover {
            background: #fafafa;
        }

        #itens input[type="number"] {
            width: 110px;
            padding: 6px;
        }

        #itens tfoot th,
        #itens tfoot td {
            font-weight: 700;
            background: #fafafa;
        }

        #itens .rownum {
            width: 42px;
            text-align: center;
            color: #444;
        }

        #itens .checkcol {
            width: 34px;
            text-align: center;
        }

        #itens .sort-ind {
            float: right;
            opacity: .75;
        }
    </style>
</head>

<body>
    <div class="container py-5">

        <h1 class="text-center mb-4">Modelos - NLLC 14.133/21</h1>

        <!-- Navegação -->
        <ul class="nav nav-tabs mb-4" id="menuTabs" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#home" role="tab">Início</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#pregao" role="tab">Pregão</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#carona" role="tab">Carona</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#dispensa" role="tab">Dispensa</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#requisitoria" role="tab">Requisitória</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#ajuda" role="tab">Ajuda</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#termo" role="tab">Termo de Referência</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#itens" role="tab">Itens de Pregão</a></li>
        </ul>

        <div class="tab-content">

            <!-- Início -->
            <div class="tab-pane fade show active" id="home" role="tabpanel">
                <div class="text-center py-4">
                    <p class="lead mb-1">Use as guias acima para navegar entre os modelos.</p>
                    <small class="text-muted">Tudo consolidado em um único arquivo <code>index.php</code>.</small>
                </div>
            </div>

            <!-- Pregão -->
            <div class="tab-pane fade" id="pregao" role="tabpanel">
                <h3>Pregão NLLC 14.133/21</h3>
                <ol>
                    <li><a class="btn btn-success my-1" href="https://www.gov.br/compras/pt-br/sistemas/conheca-o-compras/sistema-de-planejamento-e-gerenciamento-de-contratacoes/DFDnaprtica2.pdf" target="_blank" rel="noopener">DFD Digital: Documento de Formalização da Demanda</a></li>
                    <li><a class="btn btn-success my-1" href="https://www.gov.br/compras/pt-br/acesso-a-informacao/manuais/manual-fase-interna/manual-etp-digital-pdf/manual-etp-versao-2.pdf" target="_blank" rel="noopener">ETP Digital: Estudo Técnico Preliminar</a></li>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1KsHutINgYw5yxtbyFI4L47H15Z2eremu/edit?usp=sharing" target="_blank" rel="noopener">Matriz Gerenciamento de Riscos</a></li>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1KlFgcAm-mEYeEn5u2zi1em1IAOC9j2ZK/edit?usp=sharing" target="_blank" rel="noopener">Relatório de Pesquisa de Preço</a></li>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/spreadsheets/d/1KfmosfshxbJx81BHNu_dTexCP3nIm68B/edit?usp=sharing" target="_blank" rel="noopener">Mapa Comparativo de Preços</a></li>
                    <li><a class="btn btn-success my-1" data-bs-toggle="tab" href="#termo" role="tab">Termo de Referência</a></li>
                </ol>
            </div>

            <!-- Carona -->
            <div class="tab-pane fade" id="carona" role="tabpanel">
                <h3>Carona de Pregão</h3>
                <p>Documentos da SALC</p>
                <ol>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/142kgAu0SXJa4-lCZaD4V9Ox0AWWcPUDi/edit?usp=sharing" target="_blank" rel="noopener">Capa da Carona</a></li>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1WnO33Z8Y9F_SXUkqVqolJ-YsRO3Z47K2/edit?usp=sharing" target="_blank" rel="noopener">Índice da Carona</a></li>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/12wIscNS0nHccZEBEED6lE_LdmdEDMYYb/edit?usp=sharing" target="_blank" rel="noopener">Lista de Verificação da Carona</a></li>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1TOGkw38iLzl8JmnuH5n-cGP2r-5pVn1b/edit?usp=sharing" target="_blank" rel="noopener">Termo de Abertura da Carona</a></li>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1-32PHQZ6J08iM6MLZ-fEwHUlPauixQo-/edit?usp=sharing" target="_blank" rel="noopener">Termo de Encerramento</a></li>
                </ol>

                <p>Documentos do Demandante</p>
                <ol>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/194DNDWlBhPWXRLEFZeHUwdT95gcw3Q1h/edit?usp=drive_link" target="_blank" rel="noopener">Relatório da Pesquisa de Preços da Carona</a></li>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1wahSnqZi52OducBZ-nccnoXPZLHaOFS3/edit?usp=drive_link" target="_blank" rel="noopener">Justificativa da Carona</a></li>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1QHMU4KL31ck2CRBOHMMKTDYxIuJCXH9N/edit?usp=drive_link" target="_blank" rel="noopener">Demonstrativo com valores da Carona</a></li>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1fw9tmxaJug4zLY9_QiRiqgCCSaiSGN8p/edit?usp=drive_link" target="_blank" rel="noopener">Requisitória da Carona</a></li>
                </ol>
            </div>

            <!-- Dispensa -->
            <div class="tab-pane fade" id="dispensa" role="tabpanel">
                <h3>Documentos de Dispensa de Licitação</h3>
                <p>Documentos da SALC</p>
                <ol>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1LJ1ZN2LrHVApEgjN8WUWJwniiWLkwZda/edit?usp=sharing" target="_blank" rel="noopener">Capa da Dispensa</a></li>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/10BuljKVnh7L04mQacjXKKlB5PcLvSj92/edit?usp=sharing" target="_blank" rel="noopener">Índice da Dispensa</a></li>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1KD0n_IQ0V6DDGxGRu0yUxB_xPr-CrmYC/edit?usp=sharing" target="_blank" rel="noopener">Lista de Verificação da Dispensa</a></li>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1FZLuXfwmd6V4hROiAN9AXoMERJZIWY6o/edit?usp=sharing" target="_blank" rel="noopener">Aviso de Contratação Direta (SALC)</a></li>
                </ol>

                <p>Documentos do Demandante (p/ selecionar um fornecedor)</p>
                <ol>
                    <li><a class="btn btn-success my-1" href="https://drive.google.com/file/d/1vRV3S_CNKVpPCremnkJYIKgntzrcpxx5/view?usp=drive_link" target="_blank" rel="noopener">Termo de Abertura</a></li>
                    <li><a class="btn btn-success my-1" href="#termo" data-bs-toggle="tab" role="tab">Termo de Referência</a></li>
                </ol>

                <p>Documentos do Demandante (depois da seleção do fornecedor)</p>
                <ol>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1l0N3kb7lcOn4tFJk-AotT75UALBs1dhR/edit?usp=sharing" target="_blank" rel="noopener">Requisitória de Dispensa de Licitação</a></li>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1NnXmHqob_MqY-CepfvAcsP2gdr5SbewI/edit?usp=sharing" target="_blank" rel="noopener">Termo de Dispensa de Licitação</a></li>
                </ol>
            </div>

            <!-- Requisitória -->
            <div class="tab-pane fade" id="requisitoria" role="tabpanel">
                <h3>Requisitórias</h3>
                <p>Pregão da 14ª Cia E Cmb</p>
                <ol>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1TuaUblsV4vBICVbfKEcGeTGcSWL1JgJdbf9EX6zyZ9s/edit?usp=sharing" target="_blank" rel="noopener">Requisitória de Pregão da OM (14ª Cia E Cmb)</a></li>
                </ol>

                <p>Pregão de outra OM "Carona"</p>
                <ol>
                    <li><a class="btn btn-danger my-1" href="https://docs.google.com/document/d/1EHvO8UL6R8ColYnD2_SuAXgnSWDywg2n6fliwkIYmxQ/edit?usp=sharing" target="_blank" rel="noopener">Requisitória de Pregão de Outra OM (Carona)</a></li>
                </ol>

                <p>Dispensa</p>
                <ol>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1l0N3kb7lcOn4tFJk-AotT75UALBs1dhR/edit?usp=sharing" target="_blank" rel="noopener">Requisitória de Dispensa</a></li>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1NnXmHqob_MqY-CepfvAcsP2gdr5SbewI/edit?usp=sharing" target="_blank" rel="noopener">Termo de Dispensa</a></li>
                </ol>

                <p>Contrato</p>
                <ol>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1gKYr6n8fEto4GU99wXBZl15VF1PwgVfL/edit?usp=sharing" target="_blank" rel="noopener">Requisitória da Telefonia Móvel</a></li>
                </ol>

                <p>Pagamento de Auxílios</p>
                <ol>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1XC35slaUGK5Wei5VEV820WNOHkzFM0Un/edit?usp=sharing" target="_blank" rel="noopener">Requisitória de Auxílio Funeral</a></li>
                </ol>
            </div>

            <!-- Ajuda -->
            <div class="tab-pane fade" id="ajuda" role="tabpanel">
                <h3>Ajuda - Instruções</h3>
                <ol>
                    <li><a class="btn btn-success my-1" href="https://drive.google.com/drive/folders/1mKGFH-4Uas5tN4sxxySe2DWq3As9yzrS?usp=sharing" target="_blank" rel="noopener">Instruções da SALC 2025</a></li>
                    <li><a class="btn btn-success my-1" href="https://drive.google.com/drive/folders/12w9FrrY4JldTOr7ZaRbAd4NOS_3qCABb?usp=sharing" target="_blank" rel="noopener">Ata Registro de Preços - ARP</a></li>
                    <li><a class="btn btn-warning my-1" href="https://docs.google.com/document/d/1lvnCTtP3ByisEvalk-BVBSVGq4X5S38K/edit?usp=sharing" target="_blank" rel="noopener">Modelo de Assinatura p/ Contratos</a></li>
                </ol>
            </div>

            <!-- Termo de Referência -->
            <div class="tab-pane fade" id="termo" role="tabpanel">
                <h3>Termo de Referência</h3>
                <ol>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1eM88zpZbO5bclteuaMAcpKYIuf9gtZc6/edit?usp=sharing" target="_blank" rel="noopener">TR p/ Compras (perm, cons)</a></li>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/1PCgUUnuU4JqH0Jbpr21zpyYfm_IOcU2m76zfe4KshDc/edit?usp=sharing" target="_blank" rel="noopener">TR p/ TI (perm, cons)</a></li>
                    <li><a class="btn btn-success my-1" href="https://docs.google.com/document/d/10o9tOAWa9zLMYqgAZUPMQ1rqCjpf1CuL/edit?usp=sharing" target="_blank" rel="noopener">TR p/ Serviços</a></li>
                </ol>
            </div>

            <!-- Itens de Pregão (com tabela dinâmica) -->
            <div class="tab-pane fade" id="itens" role="tabpanel">
                <h3>Itens vigentes — UASG <?= _itens_h(ITENS_UASG) ?></h3>
                <div class="meta">
                    Período: <strong><?= _itens_fmt_date_br($ITENS_VIG_MIN) ?></strong> à <strong><?= _itens_fmt_date_br($ITENS_VIG_MAX) ?></strong><br>
                    <?php if (isset($itens_err)): ?>
                        <span class="text-danger">Erro ao consultar a API: <?= _itens_h($itens_err['__error']) ?></span>
                    <?php else: ?>
                        Total retornado (esta busca): <strong><?= number_format(count($itens_lista), 0, ',', '.') ?></strong> itens
                    <?php endif; ?>
                </div>

                <?php if (!isset($itens_err) && !count($itens_lista)): ?>
                    <p><strong>Nenhum item encontrado para esta UASG no período informado.</strong></p>
                <?php elseif (!isset($itens_err)): ?>
                    <table id="tPrincipal">
                        <thead>
                            <tr>
                                <th class="checkcol noclick"><input type="checkbox" id="selAll" title="Selecionar todos"></th>
                                <th class="rownum" data-sort="rownum"># <span class="sort-ind"></span></th>
                                <th data-sort="compraitem">Pregão<span class="sort-ind"></span></th>
                                <th data-sort="texto">Descrição <span class="sort-ind"></span></th>
                                <th data-sort="texto">Fornecedor <span class="sort-ind"></span></th>
                                <th class="right" data-sort="numero">Qtd <span class="sort-ind"></span></th>
                                <th class="right" data-sort="moeda">Valor Unit. <span class="sort-ind"></span></th>
                                <th class="right" data-sort="moeda">Valor Total <span class="sort-ind"></span></th>
                                <th data-sort="vigencia">Vigência <span class="sort-ind"></span></th>
                                <th data-sort="texto">Tipo do Item <span class="sort-ind"></span></th>
                                <th data-sort="texto">SICAF <span class="sort-ind"></span></th>
                                <th class="right" data-sort="numero">Qtd. Empenhada <span class="sort-ind"></span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens_lista as $idx => $it): ?>
                                <?php
                                $ord       = $idx + 1;
                                $numCompra = $it['numeroCompra'] ?? '';
                                $ano       = $it['anoCompra'] ?? '';
                                $numCompraComAno = trim($numCompra . ($ano !== '' ? "/$ano" : ''), '/');
                                [$sicafText, $sicafClass] = _itens_sicaf_label($it['situacaoSicaf'] ?? null);
                                $qtdHom  = (float)($it['quantidadeHomologadaItem'] ?? 0);
                                $vlUnit  = (float)($it['valorUnitario'] ?? 0);
                                $vlTotal = (float)($it['valorTotal'] ?? 0);
                                $qtdEmp  = (float)($it['quantidadeEmpenhada'] ?? 0);
                                $rowId   = ($it['numeroCompra'] ?? 'nc') . '-' . ($it['numeroItem'] ?? 'ni');
                                $vigIni  = $it['dataVigenciaInicial'] ?? '';
                                $vigFim  = $it['dataVigenciaFinal']   ?? '';
                                $itemNum = $it['numeroItem'] ?? '';
                                ?>
                                <tr
                                    data-compra="<?= _itens_h((int)$numCompra) ?>"
                                    data-ano="<?= _itens_h((int)$ano) ?>"
                                    data-itemnum="<?= _itens_h((int)preg_replace('/\D+/', '', $itemNum)) ?>"
                                    data-vigini="<?= _itens_h($vigIni) ?>">
                                    <td class="checkcol">
                                        <input type="checkbox" class="sel"
                                            data-rowid="<?= _itens_h($rowId) ?>"
                                            data-compra="<?= _itens_h($numCompraComAno) ?>"
                                            data-item="<?= _itens_h($it['numeroItem'] ?? '') ?>"
                                            data-desc="<?= _itens_h($it['descricaoItem'] ?? '') ?>"
                                            data-forn="<?= _itens_h($it['nomeRazaoSocialFornecedor'] ?? '') ?>"
                                            data-ni="<?= _itens_h($it['niFornecedor'] ?? '') ?>"
                                            data-qtd="<?= _itens_h($qtdHom) ?>"
                                            data-vu="<?= _itens_h($vlUnit) ?>">
                                    </td>
                                    <td class="rownum"><?= $ord ?></td>
                                    <td class="nowrap">
                                        <div><strong><?= _itens_h($numCompraComAno ?: '—') ?></strong></div>
                                        <div class="small">Item: <?= _itens_h($it['numeroItem'] ?? '—') ?></div>
                                    </td>
                                    <td>
                                        <div><?= _itens_h($it['descricaoItem'] ?? '—') ?></div>
                                        <?php if (isset($it['codigoItem'])): ?>
                                            <div class="small">Código Item: <?= _itens_h((string)$it['codigoItem']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?= _itens_h($it['nomeRazaoSocialFornecedor'] ?? '—') ?></div>
                                        <div class="small">CNPJ: <?= _itens_h(isset($it['niFornecedor']) && $it['niFornecedor'] !== '' ? _itens_fmt_cnpj($it['niFornecedor']) : '—') ?></div>
                                    </td>
                                    <td class="right"><?= number_format($qtdHom, 0, ',', '.') ?></td>
                                    <td class="right">R$ <?= number_format($vlUnit, 2, ',', '.') ?></td>
                                    <td class="right">R$ <?= number_format($vlTotal, 2, ',', '.') ?></td>
                                    <td class="nowrap"><?= _itens_fmt_date_br($vigIni) ?> à <?= _itens_fmt_date_br($vigFim) ?></td>
                                    <td class="nowrap"><?= _itens_h($it['tipoItem'] ?? '—') ?></td>
                                    <td class="<?= _itens_h($sicafClass) ?>"><?= _itens_h($sicafText) ?></td>
                                    <td class="right"><?= number_format($qtdEmp, 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <h4 class="mt-4 mb-2">Itens selecionados</h4>
                    <div class="mb-2">
                        <button class="btn-mini" id="btnCopy">Copiar tabela</button>
                        <span class="small" id="copyMsg" style="margin-left:8px;"></span>
                    </div>
                    <table id="tSel">
                        <thead>
                            <tr>
                                <th>Compra/Ano</th>
                                <th>Item</th>
                                <th>Descrição</th>
                                <th>Fornecedor</th>
                                <th class="right">Qtd. disponível</th>
                                <th class="right">Valor unitário</th>
                                <th class="right" style="width:150px;">Qtde a comprar</th>
                                <th class="right">Total da linha</th>
                                <th style="width:40px;"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="7" class="right">TOTAL</td>
                                <td class="right" id="sumTotal">R$ 0,00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <!-- JS da guia Itens -->
    <script>
        const itensPane = document.querySelector('#itens');

        function initItensTab() {
            if (!itensPane || itensPane.dataset.ready === '1') return;
            itensPane.dataset.ready = '1';

            const maskCNPJ = s => {
                const d = String(s || '').replace(/\D/g, '');
                return d.length === 14 ? `${d.slice(0,2)}.${d.slice(2,5)}.${d.slice(5,8)}/${d.slice(8,12)}-${d.slice(12)}` : (s || '');
            };
            if (!window.CSS) window.CSS = {};
            if (!CSS.escape) CSS.escape = v => String(v).replace(/["\\]/g, "\\$&");

            const fmtBRL = n => Number(n).toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
            const el = s => itensPane.querySelector(s);
            const els = s => Array.from(itensPane.querySelectorAll(s));

            const tMain = el('#tPrincipal');
            if (!tMain) return;

            const thead = tMain.querySelector('thead');
            const tBodyMain = tMain.querySelector('tbody');
            const tBodySel = el('#tSel tbody');
            const sumEl = el('#sumTotal');
            const copyBtn = el('#btnCopy');
            const copyMsg = el('#copyMsg');
            const selAll = el('#selAll');

            const parseNum = txt => {
                const n = Number(String(txt).replace(/\./g, '').replace(',', '.').replace(/[^\d.-]/g, ''));
                return Number.isFinite(n) ? n : 0;
            };
            const parseDateISO = s => {
                const d = new Date(s);
                return isNaN(d) ? 0 : d.getTime();
            };
            const parseItem = s => {
                const n = parseInt(String(s || '').replace(/\D+/g, ''), 10);
                return Number.isFinite(n) ? n : 0;
            };

            function renumberRows() {
                els('#tPrincipal tbody tr').forEach((tr, i) => {
                    const td = tr.querySelector('.rownum');
                    if (td) td.textContent = i + 1;
                });
            }

            let lastSortTh = null,
                lastDir = 1;

            function clearSortIcons() {
                els('#tPrincipal thead th .sort-ind').forEach(sp => sp.textContent = '');
            }

            function setSortIcon(th, dir) {
                const sp = th.querySelector('.sort-ind');
                if (sp) sp.textContent = (dir === 1 ? '▲' : '▼');
            }

            function keyForRow(tr, sortType, colIndex) {
                switch (sortType) {
                    case 'rownum':
                        return Array.prototype.indexOf.call(tBodyMain.children, tr);
                    case 'numero':
                        return parseNum(tr.children[colIndex].innerText.trim());
                    case 'moeda':
                        return parseNum(tr.children[colIndex].innerText.trim());
                    case 'vigencia':
                        return parseDateISO(tr.dataset.vigini || '');
                    case 'compraitem':
                        return [parseInt(tr.dataset.ano || 0, 10), parseInt(tr.dataset.compra || 0, 10), parseInt(tr.dataset.itemnum || 0, 10)];
                    case 'texto':
                    default:
                        return tr.children[colIndex].innerText.trim().toLowerCase();
                }
            }

            function cmp(a, b) {
                if (Array.isArray(a) && Array.isArray(b)) {
                    for (let i = 0; i < Math.max(a.length, b.length); i++) {
                        const da = a[i] ?? 0,
                            db = b[i] ?? 0;
                        if (da < db) return -1;
                        if (da > db) return 1;
                    }
                    return 0;
                }
                return a < b ? -1 : a > b ? 1 : 0;
            }
            thead.addEventListener('click', (e) => {
                const th = e.target.closest('th');
                if (!th || th.classList.contains('noclick')) return;
                const sortType = th.dataset.sort;
                if (!sortType) return;
                const colIndex = Array.prototype.indexOf.call(th.parentNode.children, th);
                const rows = Array.from(tBodyMain.querySelectorAll('tr'));
                const keyed = rows.map(tr => ({
                    tr,
                    key: keyForRow(tr, sortType, colIndex)
                }));
                const dir = (lastSortTh === th && lastDir === 1) ? -1 : 1;
                keyed.sort((a, b) => cmp(a.key, b.key) * dir);
                const frag = document.createDocumentFragment();
                keyed.forEach(k => frag.appendChild(k.tr));
                tBodyMain.appendChild(frag);
                lastSortTh = th;
                lastDir = dir;
                clearSortIcons();
                setSortIcon(th, dir);
                renumberRows();
            });

            function recalcTotal() {
                let sum = 0;
                els('#tSel tbody tr').forEach(tr => {
                    sum += Number(tr.dataset.tot || 0);
                });
                sumEl.textContent = fmtBRL(sum);
            }

            function parseCompraAno(s) {
                const m = String(s || '').match(/^(\d+)\s*\/\s*(\d+)$/);
                const compra = m ? parseInt(m[1], 10) : parseInt(s, 10) || 0;
                const ano = m ? parseInt(m[2], 10) : 0;
                return {
                    compra,
                    ano
                };
            }

            function compareKeys(a, b) {
                if (a.ano !== b.ano) return a.ano - b.ano;
                if (a.compra !== b.compra) return a.compra - b.compra;
                return a.item - b.item;
            }

            function keyFromData(d) {
                const ca = parseCompraAno(d.compra);
                const it = parseItem(d.item);
                return {
                    compra: ca.compra,
                    ano: ca.ano,
                    item: it
                };
            }

            function keyFromRow(tr) {
                const td = tr.querySelectorAll('td');
                const ca = parseCompraAno(td[0]?.innerText.trim() || '');
                const it = parseItem(td[1]?.innerText.trim() || '');
                return {
                    compra: ca.compra,
                    ano: ca.ano,
                    item: it
                };
            }

            function addSelectedRow(data) {
                if (tBodySel.querySelector(`tr[data-rowid="${CSS.escape(data.rowid)}"]`)) return;
                const tr = document.createElement('tr');
                tr.dataset.rowid = data.rowid;
                const max = Number(data.qtd) || 0,
                    vu = Number(data.vu) || 0,
                    init = Math.min(1, Math.max(0, max));
                tr.innerHTML = `
            <td>${data.compra}</td>
            <td class="nowrap">${data.item}</td>
            <td>${data.desc}</td>
            <td>${data.forn}<div class="small">CNPJ: ${maskCNPJ(data.ni) || '—'}</div></td>
            <td class="right">${max.toLocaleString('pt-BR')}</td>
            <td class="right">${fmtBRL(vu)}</td>
            <td class="right"><input type="number" min="1" max="${max}" step="1" value="${init}" class="qtdBuy"></td>
            <td class="right totCell"></td>
            <td><button class="btn-mini btnDel" title="Remover">×</button></td>`;
                const input = tr.querySelector('.qtdBuy'),
                    totCell = tr.querySelector('.totCell');

                function updateLine() {
                    let q = parseInt(input.value, 10);
                    if (!Number.isInteger(q) || q < 1) q = 1;
                    if (q > max) q = max;
                    input.value = q;
                    const tot = q * vu;
                    tr.dataset.tot = String(tot);
                    totCell.textContent = fmtBRL(tot);
                    recalcTotal();
                }
                input.addEventListener('input', updateLine);
                input.addEventListener('blur', updateLine);
                tr.querySelector('.btnDel').addEventListener('click', () => {
                    const mainCb = tBodyMain.querySelector(`input.sel[data-rowid="${CSS.escape(data.rowid)}"]`);
                    if (mainCb) {
                        mainCb.checked = false;
                        mainCb.closest('tr')?.classList.remove('selrow');
                    }
                    tr.remove();
                    recalcTotal();
                });
                const newK = keyFromData(data);
                const rows = Array.from(tBodySel.querySelectorAll('tr'));
                let inserted = false;
                for (const r of rows) {
                    if (compareKeys(newK, keyFromRow(r)) < 0) {
                        tBodySel.insertBefore(tr, r);
                        inserted = true;
                        break;
                    }
                }
                if (!inserted) tBodySel.appendChild(tr);
                updateLine();
            }

            function removeSelectedRow(id) {
                const tr = tBodySel.querySelector(`tr[data-rowid="${CSS.escape(id)}"]`);
                if (tr) {
                    tr.remove();
                    recalcTotal();
                }
            }

            tBodyMain.addEventListener('change', e => {
                const cb = e.target;
                if (!cb.classList || !cb.classList.contains('sel')) return;
                const data = {
                    rowid: cb.dataset.rowid,
                    compra: cb.dataset.compra,
                    item: cb.dataset.item,
                    desc: cb.dataset.desc,
                    forn: cb.dataset.forn,
                    ni: cb.dataset.ni,
                    qtd: cb.dataset.qtd,
                    vu: cb.dataset.vu
                };
                if (cb.checked) {
                    cb.closest('tr')?.classList.add('selrow');
                    addSelectedRow(data);
                } else {
                    cb.closest('tr')?.classList.remove('selrow');
                    removeSelectedRow(data.rowid);
                }
            });

            selAll?.addEventListener('change', () => {
                els('#tPrincipal tbody input.sel').forEach(cb => {
                    if (cb.checked !== selAll.checked) {
                        cb.checked = selAll.checked;
                        cb.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    }
                });
            });

            copyBtn?.addEventListener('click', async () => {
                const src = itensPane.querySelector('#tSel');
                const hdr = Array.from(src.querySelectorAll('thead th')).map(th => th.innerText.trim());
                const rows = Array.from(src.querySelectorAll('tbody tr'));
                const totalTxt = itensPane.querySelector('#sumTotal')?.innerText?.trim() || 'R$ 0,00';
                const td = (txt, tag = 'td', align = 'left') => `<${tag} style="border:1px solid #ccc; padding:6px; text-align:${align}; vertical-align:top;">${txt}</${tag}>`;
                let html = `<meta charset="utf-8"><table style="border-collapse:collapse; border:1px solid #ccc; font-family:Arial,Helvetica,sans-serif; font-size:13px;"><thead><tr>${hdr.map(h=>td(h,'th')).join('')}</tr></thead><tbody>`;
                rows.forEach(tr => {
                    const tds = tr.querySelectorAll('td');
                    const compra = tds[0].innerText.trim();
                    const item = tds[1].innerText.trim();
                    const desc = tds[2].innerText.trim();
                    const forn = tds[3].childNodes[0].textContent.trim();
                    const idRaw = (tds[3].querySelector('.small')?.innerText || '');
                    const idOnly = idRaw.replace(/^(?:NI|CNPJ)\s*:\s*/i, '').trim();
                    const cnpjFmt = maskCNPJ(idOnly);
                    const qtdDisp = tds[4].innerText.trim();
                    const vUnit = tds[5].innerText.trim();
                    const qBuy = tds[6].querySelector('input')?.value || '';
                    const tot = tds[7].innerText.trim();
                    html += `<tr>${td(compra)}${td(item)}${td(`${desc}<div style='color:#666;font-size:12px'>CNPJ: ${cnpjFmt}</div>`)}${td(forn)}${td(qtdDisp,'td','right')}${td(vUnit,'td','right')}${td(qBuy,'td','right')}${td(tot,'td','right')}${td('')}</tr>`;
                });
                html += `</tbody><tfoot><tr>${td('TOTAL','th','right')}<td colspan="6"></td>${td(totalTxt,'th','right')}<td></td></tr></tfoot></table>`;
                try {
                    if (navigator.clipboard && window.ClipboardItem) {
                        const data = {
                            'text/html': new Blob([html], {
                                type: 'text/html'
                            }),
                            'text/plain': new Blob([html], {
                                type: 'text/plain'
                            })
                        };
                        await navigator.clipboard.write([new ClipboardItem(data)]);
                    } else {
                        const div = document.createElement('div');
                        div.contentEditable = 'true';
                        div.style.position = 'fixed';
                        div.style.left = '-99999px';
                        div.innerHTML = html;
                        document.body.appendChild(div);
                        const range = document.createRange();
                        range.selectNodeContents(div);
                        const sel = window.getSelection();
                        sel.removeAllRanges();
                        sel.addRange(range);
                        document.execCommand('copy');
                        document.body.removeChild(div);
                    }
                    if (copyMsg) copyMsg.textContent = 'Tabela (formatada) copiada para a área de transferência.';
                } catch (e) {
                    console.error(e);
                    if (copyMsg) copyMsg.textContent = 'Não foi possível copiar a tabela.';
                }
                setTimeout(() => {
                    if (copyMsg) copyMsg.textContent = '';
                }, 3500);
            });
        }

        const itensTabLink = document.querySelector('a[href="#itens"]');
        itensTabLink?.addEventListener('shown.bs.tab', initItensTab);
        if (location.hash === '#itens') initItensTab();
    </script>
</body>

</html>