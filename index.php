<?php
require_once __DIR__ . '/app/config.php';
header('Content-Type: text/html; charset=utf-8');

// redireciona handler antigo de certidão para o endpoint dedicado
if (isset($_GET['certidao'])) {
    $cnpj = preg_replace('/\D+/', '', (string) $_GET['certidao']);
    header('Location: api/certidao.php?cnpj=' . $cnpj, true, 302);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" crossorigin="anonymous">
    <title>Modelos - NLLC 14.133/21</title>
    <link rel="icon" href="https://www.gov.br/compras/pt-br/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/app.css">
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
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#requisitoria"
                    role="tab">Requisitórias</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#ajuda" role="tab">Ajuda</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#termo" role="tab">Termo de
                    Referência</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#itens" role="tab">Itens de Pregão</a>
            </li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#atas" role="tab">Lista de Atas</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#ugs" role="tab">UGs</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#sped" role="tab">Processos Digitais SPED 3.0</a></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="home" role="tabpanel">
                <?php include __DIR__ . '/views/home.php'; ?>
            </div>
            <div class="tab-pane fade" id="pregao" role="tabpanel">
                <?php include __DIR__ . '/views/pregao.php'; ?>
            </div>
            <div class="tab-pane fade" id="carona" role="tabpanel">
                <?php include __DIR__ . '/views/carona.php'; ?>
            </div>
            <div class="tab-pane fade" id="dispensa" role="tabpanel">
                <?php include __DIR__ . '/views/dispensa.php'; ?>
            </div>
            <div class="tab-pane fade" id="requisitoria" role="tabpanel">
                <?php include __DIR__ . '/views/requisitoria.php'; ?>
            </div>
            <div class="tab-pane fade" id="ajuda" role="tabpanel">
                <?php include __DIR__ . '/views/ajuda.php'; ?>
            </div>
            <div class="tab-pane fade" id="termo" role="tabpanel">
                <?php include __DIR__ . '/views/termo.php'; ?>
            </div>
            <div class="tab-pane fade" id="itens" role="tabpanel">
                <?php include __DIR__ . '/views/itens.php'; ?>
            </div>
            <div class="tab-pane fade" id="atas" role="tabpanel">
                <?php include __DIR__ . '/views/atas.php'; ?>
            </div>
            <div class="tab-pane fade" id="ugs" role="tabpanel">
                <?php include __DIR__ . '/views/ugs.php'; ?>
            </div>
            <div class="tab-pane fade" id="sped" role="tabpanel">
                <?php include __DIR__ . '/views/sped.php'; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous">
    </script>
    <script src="assets/js/app.js"></script>
</body>

</html>
