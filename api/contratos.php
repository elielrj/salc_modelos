<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/api/ContratosClient.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $tz = new DateTimeZone(APP_TIMEZONE);
    $today = new DateTime('now', $tz);

    // Busca automática: últimos 365 dias até hoje
    $vigMin = (clone $today)->modify('-365 days')->format('Y-m-d');
    $vigMax = $today->format('Y-m-d');

    // Monta somente os parâmetros solicitados
    $params = [
        'tamanhoPagina' => 100,
        'codigoUnidadeGestora' => (string) UASG,
        'dataVigenciaInicialMin' => $vigMin,
        'dataVigenciaInicialMax' => $vigMax,
    ];

    $lista = [];
    $p = 1;
    while (true) {
        $resp = ContratosClient::listPage($params, $p, true, CACHE_TTL, MAX_RETRIES, BASE_BACKOFF);
        if (isset($resp['__error'])) {
            http_response_code(502);
            echo json_encode(['error' => $resp['__error'], 'debug' => $resp['__debug'] ?? null, 'params' => $params]);
            exit;
        }
        $lote = $resp['resultado'] ?? [];
        if (!is_array($lote) || !count($lote)) break;
        foreach ($lote as $rec) { $lista[] = $rec; }
        $rest = $resp['paginasRestantes'] ?? null;
        if ($rest !== null && (int)$rest <= 0) break;
        $p++; if ($p > 2000) break;
        if (REQUEST_DELAY_MS > 0) usleep(REQUEST_DELAY_MS * 1000);
    }

    echo json_encode([
        'filtros' => [
            'codigoUnidadeGestora' => (string) UASG,
            'dataVigenciaInicialMin' => $vigMin,
            'dataVigenciaInicialMax' => $vigMax,
            'tamanhoPagina' => 100
        ],
        'total' => count($lista),
        'contratos' => $lista,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'internal', 'message' => $e->getMessage()]);
}
