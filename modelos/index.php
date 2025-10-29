<?php
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" crossorigin="anonymous">
    <title>Modelos | SALC</title>
    <link rel="icon" href="https://www.gov.br/compras/pt-br/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/app.css">
</head>

<body>
    <div class="container py-5">
        <div class="text-center text-md-start mb-5">
            <h1 class="mb-3">Sistema SALC — Modelos</h1>
            <p class="lead mb-3">Coleção de modelos de processos e documentos utilizada na SALC.</p>
            <a class="btn btn-success" href="http://10.34.156.121/" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right me-1"></i>
                Abrir módulo de Itens &amp; Consultas
            </a>
        </div>

        <div class="bg-light rounded-3 shadow-sm p-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                <h2 class="mb-0 text-center text-md-start flex-grow-1">Modelos de Processos e Documentos</h2>
                <span class="text-muted small text-center text-md-end">Selecione uma guia abaixo para abrir o conteúdo desejado.</span>
            </div>

            <nav class="navbar navbar-expand-lg navbar-light bg-white rounded shadow-sm mb-4" aria-label="Navegação de modelos">
                <div class="container-fluid">
                    <a class="navbar-brand d-none d-lg-inline" href="index.php">Modelos</a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#modelosNav" aria-controls="modelosNav" aria-expanded="false" aria-label="Alternar navegação">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="modelosNav">
                        <ul class="navbar-nav mx-auto gap-lg-2" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#pregao" role="tab">Pregão</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#dispensa" role="tab">Dispensa</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#carona" role="tab">Carona</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#requisitoria" role="tab">Requisitórias</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#termo" role="tab">Termo de Referência</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#sped" role="tab">SPED 3.0</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="pregao" role="tabpanel">
                    <?php include __DIR__ . '/views/pregao.php'; ?>
                </div>
                <div class="tab-pane fade" id="dispensa" role="tabpanel">
                    <?php include __DIR__ . '/views/dispensa.php'; ?>
                </div>
                <div class="tab-pane fade" id="carona" role="tabpanel">
                    <?php include __DIR__ . '/views/carona.php'; ?>
                </div>
                <div class="tab-pane fade" id="requisitoria" role="tabpanel">
                    <?php include __DIR__ . '/views/requisitoria.php'; ?>
                </div>
                <div class="tab-pane fade" id="termo" role="tabpanel">
                    <?php include __DIR__ . '/views/termo.php'; ?>
                </div>
                <div class="tab-pane fade" id="sped" role="tabpanel">
                    <?php include __DIR__ . '/views/sped.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>

</html>
