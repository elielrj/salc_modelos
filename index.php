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
    <title>Modelos &amp; Controle de Pregões e Contratos</title>
    <link rel="icon" href="https://www.gov.br/compras/pt-br/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/app.css">
</head>

<body>
    <div class="container-fluid py-5">
        <h1 class="text-center mb-4">Modelos &amp; Controle de Pregões e Contratos</h1>

        <!-- Navegação (agrupada e responsiva) -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light rounded shadow-sm mb-4" aria-label="Navegação principal">
            <div class="container-fluid">
                <a class="navbar-brand d-none d-lg-inline" href="#">Menu</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Alternar navegação">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav mx-auto align-items-lg-center gap-lg-2" id="menuTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#ugs" role="tab"><i class="bi bi-building me-1"></i> UGs</a>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navModelos" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-file-earmark-text me-1"></i> Modelos
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navModelos">
                                <li><h6 class="dropdown-header">Modelos de Processos</h6></li>
                                <li><a class="dropdown-item" data-bs-toggle="tab" href="#pregao" role="tab">Pregão</a></li>
                                <li><a class="dropdown-item" data-bs-toggle="tab" href="#dispensa" role="tab">Dispensa</a></li>
                                <li><a class="dropdown-item" data-bs-toggle="tab" href="#carona" role="tab">Carona</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">Modelos de Documentos</h6></li>
                                <li><a class="dropdown-item" data-bs-toggle="tab" href="#requisitoria" role="tab">Requisitórias</a></li>
                                <li><a class="dropdown-item" data-bs-toggle="tab" href="#termo" role="tab">Termo de Referência</a></li>
                            </ul>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navItens" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-list-check me-1"></i> Itens
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navItens">
                                <li><a class="dropdown-item" data-bs-toggle="tab" href="#itens" role="tab">Itens de Pregão</a></li>
                                <li><a class="dropdown-item" data-bs-toggle="tab" href="#itens-carona" role="tab">Itens de Pregão Carona</a></li>
                                <li><a class="dropdown-item" data-bs-toggle="tab" href="#contratos" role="tab">Itens Contrato</a></li>
                            </ul>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navConsultas" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-search me-1"></i> Consultas
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navConsultas">
                                <li><a class="dropdown-item" data-bs-toggle="tab" href="#atas" role="tab">Lista de Atas</a></li>
                                <li><a class="dropdown-item" data-bs-toggle="tab" href="#contratos-listar" role="tab">Listar Contratos</a></li>
                                <li><a class="dropdown-item" data-bs-toggle="tab" href="#sped" role="tab">SPED 3.0</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="tab-content">
            <div class="tab-pane fade" id="pregao" role="tabpanel">
                <?php include __DIR__ . '/views/pregao.php'; ?>
            </div>
            <div class="tab-pane fade" id="carona" role="tabpanel">
                <?php include __DIR__ . '/views/carona.php'; ?>
            </div>
            <div class="tab-pane fade" id="itens-carona" role="tabpanel">
                <?php include __DIR__ . '/views/itens_carona.php'; ?>
            </div>
            <div class="tab-pane fade" id="dispensa" role="tabpanel">
                <?php include __DIR__ . '/views/dispensa.php'; ?>
            </div>
            <div class="tab-pane fade" id="requisitoria" role="tabpanel">
                <?php include __DIR__ . '/views/requisitoria.php'; ?>
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
            <div class="tab-pane fade show active" id="ugs" role="tabpanel">
                <?php include __DIR__ . '/views/ugs.php'; ?>
            </div>
            <div class="tab-pane fade" id="sped" role="tabpanel">
                <?php include __DIR__ . '/views/sped.php'; ?>
            </div>
            <div class="tab-pane fade" id="contratos" role="tabpanel">
                <?php include __DIR__ . '/views/contratos.php'; ?>
            </div>
            <div class="tab-pane fade" id="contratos-listar" role="tabpanel">
                <?php include __DIR__ . '/views/contratos_listar.php'; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous">
    </script>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/itens_carona.js"></script>
</body>

</html>
