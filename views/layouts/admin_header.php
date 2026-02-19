<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= htmlspecialchars($pageTitle ?? 'Admin Panel')?> | Turnero Admin
    </title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="/assets/css/admin.css" rel="stylesheet">
</head>

<body class="admin-body">

    <div class="d-flex admin-wrapper">
        <!-- Sidebar -->
        <nav class="admin-sidebar">
            <div class="sidebar-brand">
                <i class="bi bi-calendar-check-fill"></i>
                <span>Turnero</span>
            </div>
            <ul class="sidebar-nav">
                <li>
                    <a href="/admin/dashboard"
                        class="<?= str_contains($_SERVER['REQUEST_URI'], 'dashboard') ? 'active' : ''?>">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="/admin/appointments"
                        class="<?= str_contains($_SERVER['REQUEST_URI'], 'appointments') ? 'active' : ''?>">
                        <i class="bi bi-calendar3"></i> Turnos
                    </a>
                </li>
                <li>
                    <a href="/admin/services"
                        class="<?= str_contains($_SERVER['REQUEST_URI'], 'services') ? 'active' : ''?>">
                        <i class="bi bi-grid-1x2"></i> Servicios
                    </a>
                </li>
                <li>
                    <a href="/admin/hours" class="<?= str_contains($_SERVER['REQUEST_URI'], 'hours') ? 'active' : ''?>">
                        <i class="bi bi-clock"></i> Horarios
                    </a>
                </li>
                <li>
                    <a href="/admin/settings"
                        class="<?= str_contains($_SERVER['REQUEST_URI'], 'settings') ? 'active' : ''?>">
                        <i class="bi bi-gear"></i> Configuración
                    </a>
                </li>
                <li class="mt-auto">
                    <a href="/logout" class="text-danger">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main content -->
        <div class="admin-content flex-grow-1">
            <div class="admin-topbar">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="mb-0">
                    <?= htmlspecialchars($pageTitle ?? 'Panel de Administración')?>
                </h5>
                <span class="ms-auto text-muted small">
                    <i class="bi bi-person-circle me-1"></i>
                    <?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin')?>
                </span>
            </div>
            <div class="admin-page-body">