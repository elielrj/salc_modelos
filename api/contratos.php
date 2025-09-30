<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/api/ContratosClient.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $tz = new DateTimeZone(APP_TIMEZONE);
    $today = new DateTime('now', $tz);

    // Consultas em janelas de 12 meses desde 2012 até o ano corrente
    $lista = [];
    $uniq = [];
    $startYear = 2012;
    $endYear = (int)$today->format('Y');
    for ($yr = $startYear; $yr <= $endYear; $yr++) {
        $vigMin = sprintf('%04d-01-01', $yr);
        $vigMax = sprintf('%04d-12-31', $yr);
        // Parâmetros por janela
        $params = [
            'tamanhoPagina' => 100,
            'codigoUnidadeGestora' => (string) UASG,
            'dataVigenciaInicialMin' => $vigMin,
            'dataVigenciaInicialMax' => $vigMax,
        ];
        $p = 1;
        while (true) {
            $resp = ContratosClient::listPage($params, $p, true, CACHE_TTL, MAX_RETRIES, BASE_BACKOFF);
            if (isset($resp['__error'])) {
                http_response_code(502);
                echo json_encode(['error' => $resp['__error'], 'debug' => (isset($resp['__debug']) ? $resp['__debug'] : null), 'params' => $params]);
                exit;
            }
            $lote = $resp['resultado'] ?? [];
            if (!is_array($lote) || !count($lote)) break;
            foreach ($lote as $rec) {
                $key = (
                    ($rec['numeroContrato'] ?? '') . '|' .
                    ($rec['numeroCompra'] ?? '') . '|' .
                    ($rec['anoCompra'] ?? '') . '|' .
                    ($rec['numeroItem'] ?? '') . '|' .
                    ($rec['niFornecedor'] ?? '') . '|' .
                    ($rec['valorUnitarioItem'] ?? '')
                );
                if (isset($uniq[$key])) continue;
                $uniq[$key] = 1;
                $lista[] = $rec;
            }
            $rest = $resp['paginasRestantes'] ?? null;
            if ($rest !== null && (int)$rest <= 0) break;
            $p++; if ($p > 2000) break;
        }
    }

    echo json_encode([
        'filtros' => [
            'codigoUnidadeGestora' => (string) UASG,
            'periodo' => [$startYear, $endYear],
            'tamanhoPagina' => 100
        ],
        'total' => count($lista),
        'contratos' => $lista,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'internal', 'message' => $e->getMessage()]);
}
