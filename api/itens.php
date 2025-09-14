<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/api/ARPItensClient.php';
require_once __DIR__ . '/../app/models/Item.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $tz = new DateTimeZone(APP_TIMEZONE);
    $today = new DateTime('now', $tz);
    $vigMin = (clone $today)->modify('-365 days')->format('Y-m-d');
    $vigMax = (clone $today)->modify('+365 days')->format('Y-m-d');

    $uasg = (int)($_GET['uasg'] ?? UASG);
    $paramsBase = [
        'tamanhoPagina' => 50,
        'codigoUnidadeGerenciadora' => $uasg,
        'dataVigenciaInicialMin' => $vigMin,
        'dataVigenciaInicialMax' => $vigMax,
    ];

    $lista = [];
    $seen = [];
    $p = 1;
    while (true) {
        $resp = ARPItensClient::listPage($paramsBase, $p, true, CACHE_TTL, MAX_RETRIES, BASE_BACKOFF);
        if (isset($resp['__error'])) {
            http_response_code(502);
            echo json_encode(['error' => $resp['__error'], 'debug' => $resp['__debug'] ?? null]);
            exit;
        }
        $lote = $resp['resultado'] ?? [];
        if (!is_array($lote) || !count($lote)) break;
        foreach ($lote as $rec) {
            $k = ($rec['numeroCompra'] ?? '') . '|' . ($rec['anoCompra'] ?? '') . '|' . ($rec['numeroItem'] ?? '');
            if (!isset($seen[$k])) {
                $lista[] = Item::fromApi($rec);
                $seen[$k] = true;
            }
        }
        $rest = $resp['paginasRestantes'] ?? null;
        if ($rest !== null && (int)$rest <= 0) break;
        $p++; if ($p > 2000) break;
        if (REQUEST_DELAY_MS > 0) usleep(REQUEST_DELAY_MS * 1000);
    }
    echo json_encode(['uasg' => $uasg, 'vigencia' => [$vigMin, $vigMax], 'total' => count($lista), 'itens' => $lista]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'internal', 'message' => $e->getMessage()]);
}

