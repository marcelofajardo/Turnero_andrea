<?php $base_path = $_ENV['APP_BASE_PATH'] ?? ''; ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= htmlspecialchars($pageTitle ?? ($businessName ?? 'Turnero'), ENT_QUOTES)?>
    </title>
    <meta name="description"
        content="Sistema de reserva de turnos online. Reservá tu turno de forma rápida y sencilla.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link href="<?= $base_path?>/assets/css/style.css" rel="stylesheet">
</head>

<body data-base-path="<?= $base_path?>">

    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= $base_path?>/">
                <i class="bi bi-calendar-check-fill me-2"></i>
                <?= htmlspecialchars($businessName ?? 'Turnero')?>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="<?= $base_path?>/">Reservar Turno</a></li>
                    <li class="nav-item ms-lg-2"><a class="btn btn-primary btn-sm px-3"
                            href="<?= $base_path?>/login">Admin</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main>