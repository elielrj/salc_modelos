<?php
require_once __DIR__ . '/../app/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $file = __DIR__ . '/../data/ugs.csv';
    if (!is_file($file)) {
        http_response_code(404);
        echo json_encode(['error' => 'csv_not_found']);
        exit;
    }
    $fh = fopen($file, 'r');
    if (!$fh) throw new RuntimeException('cannot_open_csv');
    $header = fgetcsv($fh);
    $rows = [];
    while (($r = fgetcsv($fh)) !== false) {
        if (count($r) < 4) continue;
        $rows[] = [
            'codug' => trim($r[0]),
            'sigla' => trim($r[1]),
            'cma'   => trim($r[2]),
            'cidade_estado' => trim($r[3]),
        ];
    }
    fclose($fh);
    echo json_encode(['total' => count($rows), 'ugs' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'internal', 'message' => $e->getMessage()]);
}

