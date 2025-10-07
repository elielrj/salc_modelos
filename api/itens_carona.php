<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/api/ARPItensClient.php';
require_once __DIR__ . '/../app/models/Item.php';

header('Content-Type: application/json; charset=utf-8');

try {
    @set_time_limit(0);
    $tz = new DateTimeZone(APP_TIMEZONE);
    $today = new DateTime('now', $tz);
    $vigMin = (clone $today)->modify('-365 days')->format('Y-m-d');
    $vigMax = (clone $today)->modify('+365 days')->format('Y-m-d');

    $csvPath = __DIR__ . '/../data/ugs.csv';
    if (!is_file($csvPath)) {
        throw new RuntimeException('Arquivo data/ugs.csv não encontrado.');
    }

    $uasgs = [];
    $fh = fopen($csvPath, 'r');
    if (!$fh) {
        throw new RuntimeException('Falha ao abrir data/ugs.csv.');
    }
    $header = fgetcsv($fh, 0, ',');
    while (($row = fgetcsv($fh, 0, ',')) !== false) {
        $cod = trim((string) (isset($row[0]) ? $row[0] : ''));
        if ($cod === '' || $cod === '160517') {
            continue;
        }
        if (!preg_match('/^\d+$/', $cod)) {
            continue;
        }
        $uasgs[$cod] = [
            'codug' => $cod,
            'sigla' => trim((string) (isset($row[1]) ? $row[1] : '')),
            'cma' => trim((string) (isset($row[2]) ? $row[2] : '')),
            'descricao' => trim((string) (isset($row[3]) ? $row[3] : '')),
        ];
    }
    fclose($fh);

    if (!$uasgs) {
        throw new RuntimeException('Nenhuma UASG encontrada para consulta.');
    }
    ksort($uasgs, SORT_NUMERIC);

    $lista = [];
    $seen = [];
    $consultas = 0;
    $falhas = [];
    foreach ($uasgs as $cod => $meta) {
        $consultas++;
        $params = [
            'tamanhoPagina' => 50,
            'codigoUnidadeGerenciadora' => (int)$cod,
            'dataVigenciaInicialMin' => $vigMin,
            'dataVigenciaInicialMax' => $vigMax,
        ];
        $pagina = 1;
        while (true) {
            $resp = ARPItensClient::listPage($params, $pagina, true, CACHE_TTL, MAX_RETRIES, BASE_BACKOFF);
            if (isset($resp['__error'])) {
                $falhas[] = [
                    'uasg' => $cod,
                    'message' => $resp['__error'],
                    'debug' => isset($resp['__debug']) ? $resp['__debug'] : null,
                    'pagina' => $pagina,
                ];
                break;
            }
            $lote = isset($resp['resultado']) ? $resp['resultado'] : array();
            if (!is_array($lote) || !count($lote)) {
                break;
            }
            foreach ($lote as $rec) {
                $item = Item::fromApi($rec);
                if (empty($item['codigoUnidadeGerenciadora'])) {
                    $item['codigoUnidadeGerenciadora'] = $cod;
                }
                if (empty($item['nomeUnidadeGerenciadora'])) {
                    $item['nomeUnidadeGerenciadora'] = $meta['descricao'] ?: $meta['sigla'];
                }
                if (empty($item['siglaUnidadeGerenciadora']) && $meta['sigla']) {
                    $item['siglaUnidadeGerenciadora'] = $meta['sigla'];
                }
                $key = sprintf('%s|%s|%s|%s',
                    (string) (isset($item['codigoUnidadeGerenciadora']) ? $item['codigoUnidadeGerenciadora'] : $cod),
                    (string) (isset($item['numeroCompra']) ? $item['numeroCompra'] : ''),
                    (string) (isset($item['anoCompra']) ? $item['anoCompra'] : ''),
                    (string) (isset($item['numeroItem']) ? $item['numeroItem'] : '')
                );
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $lista[] = $item;
            }
            $rest = isset($resp['paginasRestantes']) ? $resp['paginasRestantes'] : null;
            if ($rest !== null && (int)$rest <= 0) {
                break;
            }
            $pagina++;
            if ($pagina > 2000) {
                break;
            }
        }
    }

    echo json_encode([
        'vigencia' => [$vigMin, $vigMax],
        'total' => count($lista),
        'itens' => $lista,
        'uasgsConsultadas' => array_values($uasgs),
        'consultasEfetuadas' => $consultas,
        'falhas' => $falhas,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'internal',
        'message' => $e->getMessage(),
    ]);
}
