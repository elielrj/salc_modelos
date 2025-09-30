<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/api/ARPAtaClient.php';
require_once __DIR__ . '/../app/models/Ata.php';

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
        $resp = ARPAtaClient::listPage($paramsBase, $p, true, CACHE_TTL, MAX_RETRIES, BASE_BACKOFF);
        if (isset($resp['__error'])) {
            http_response_code(502);
            echo json_encode(['error' => $resp['__error'], 'debug' => (isset($resp['__debug']) ? $resp['__debug'] : null)]);
            exit;
        }
        $lote = $resp['resultado'] ?? [];
        if (!is_array($lote) || !count($lote)) break;
        foreach ($lote as $rec) {
            $k = ($rec['numeroCompra'] ?? '') . '|' . ($rec['anoCompra'] ?? '') . '|' . ($rec['numeroAtaRegistroPreco'] ?? ($rec['numeroAta'] ?? '')) . '|' . ($rec['idCompra'] ?? ($rec['id'] ?? ''));
            if (!isset($seen[$k])) {
                $lista[] = Ata::fromApi($rec);
                $seen[$k] = true;
            }
        }
        $rest = $resp['paginasRestantes'] ?? null;
        if ($rest !== null && (int)$rest <= 0) break;
        $p++; if ($p > 2000) break;
    }
    echo json_encode(['uasg' => $uasg, 'vigencia' => [$vigMin, $vigMax], 'total' => count($lista), 'atas' => $lista]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'internal', 'message' => $e->getMessage()]);
}
