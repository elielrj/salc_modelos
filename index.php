<?php
$itensBase = 'http://10.34.156.121/';

if (isset($_GET['certidao'])) {
    $cnpj = preg_replace('/\D+/', '', (string) $_GET['certidao']);
    header('Location: ' . $itensBase . 'api/certidao.php?cnpj=' . $cnpj, true, 302);
    exit;
}

header('Location: ./modelos/', true, 302);
exit;
