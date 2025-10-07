<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/api/TCUClient.php';

$cnpjParam = isset($_GET['cnpj']) ? $_GET['cnpj'] : '';
$cnpj = preg_replace('/\D+/', '', (string) $cnpjParam);
if (strlen($cnpj) !== 14) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "CNPJ inválido. Informe 14 dígitos.";
    exit;
}

$ret = TCUClient::getCertidaoPdf($cnpj);
if (is_array($ret) && isset($ret['__error'])) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Falha ao obter certidão do TCU.";
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="certidao-'.$cnpj.'.pdf"');
echo $ret;
