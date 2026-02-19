<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Admin | Turnero</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="/assets/css/admin.css" rel="stylesheet">
</head>

<body class="login-body">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="text-center mb-4">
                <div class="login-logo"><i class="bi bi-calendar-check-fill"></i></div>
                <h3 class="fw-bold mt-3">Panel Admin</h3>
                <p class="text-muted small">Ingresá con tus credenciales</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-sm">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error)?>
            </div>
            <?php
endif; ?>

            <form method="POST" action="/login" autocomplete="off">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf)?>">
                <div class="mb-3">
                    <label class="form-label fw-500">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="admin"
                            autocomplete="username" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-500">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="••••••••"
                            autocomplete="current-password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-600">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Ingresar
                </button>
            </form>

            <p class="text-center mt-3 mb-0">
                <a href="/" class="text-muted small"><i class="bi bi-arrow-left me-1"></i>Volver al sitio</a>
            </p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>