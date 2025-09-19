<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/api/ContratosListaClient.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $tz = new DateTimeZone(APP_TIMEZONE);
    $today = new DateTime('now', $tz);

    // UASG fixo solicitado
    $uasg = '160517';

    // Busca por janelas de 12 meses desde 2012 até o ano atual
    $lista = [];
    $uniq = [];
    $startYear = 2012;
    $endYear = (int)$today->format('Y');
    $totalRegistros = 0; $totalPaginas = 1; // informar algo razoável
    for ($year=$startYear; $year <= $endYear; $year++) {
        $vigMin = sprintf('%04d-01-01', $year);
        $vigMax = sprintf('%04d-12-31', $year);
        $params = [
            'tamanhoPagina' => 100,
            'codigoUnidadeGestora' => $uasg,
            'dataVigenciaInicialMin' => $vigMin,
            'dataVigenciaInicialMax' => $vigMax,
        ];
        $p = 1;
        while (true) {
            $resp = ContratosListaClient::listPage($params, $p, false, CACHE_TTL, MAX_RETRIES, BASE_BACKOFF);
            if (isset($resp['__error'])) {
                http_response_code(502);
                echo json_encode(['error' => $resp['__error'], 'debug' => (isset($resp['__debug']) ? $resp['__debug'] : null), 'params' => $params]);
                exit;
            }
            $lote = isset($resp['resultado']) && is_array($resp['resultado']) ? $resp['resultado'] : [];
            foreach ($lote as $rec) {
                $key = (string)($rec['numeroContrato'] ?? '') . '|' . (string)($rec['numeroCompra'] ?? '');
                if (isset($uniq[$key])) continue; $uniq[$key] = 1; $lista[] = $rec;
            }
            $rest = $resp['paginasRestantes'] ?? null;
            if ($rest !== null && (int)$rest <= 0) break;
            $p++; if ($p > 2000) break;
            if (REQUEST_DELAY_MS > 0) usleep(REQUEST_DELAY_MS * 1000);
        }
        // aguarda 1 a 5 segundos entre janelas
        usleep(mt_rand(1000,5000) * 1000);
    }

    // Deduplicar por numeroContrato+numeroCompra
    $uniq = [];
    $dedup = [];
    foreach ($lista as $rec) {
        $nc = isset($rec['numeroContrato']) ? (string)$rec['numeroContrato'] : '';
        $comp = isset($rec['numeroCompra']) ? (string)$rec['numeroCompra'] : '';
        $key = $nc . '|' . $comp;
        if (isset($uniq[$key])) continue;
        $uniq[$key] = true; $dedup[] = $rec;
    }

    $debug = [
        'sampleUrl' => ContratosListaClient::BASE_URL . '?' . http_build_query($params + ['pagina' => 1], '', '&', PHP_QUERY_RFC3986),
    ];

    echo json_encode([
        'filtros' => [
            'codigoUnidadeGestora' => $uasg,
            'periodo' => [$startYear, $endYear],
            'tamanhoPagina' => 100
        ],
        'resultado' => $dedup,
        'totalRegistros' => count($dedup),
        'totalPaginas' => 1,
        'paginasRestantes' => 0,
        'debug' => ['sampleUrl' => ContratosListaClient::BASE_URL],
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'internal', 'message' => $e->getMessage()]);
}
