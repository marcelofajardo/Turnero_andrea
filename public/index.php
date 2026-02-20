<?php

declare(strict_types=1);

/**
 * Front Controller — single entry point for all HTTP requests.
 */

// --- Error handling ---
ini_set('display_errors', ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? '1' : '0');
set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline): never {
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\WebhookController;

// --- Load environment ---
$dotenv = Dotenv::createMutable(dirname(__DIR__));
$dotenv->load();

// --- Session configuration ---
$sessionSavePath = $_ENV['SESSION_SAVE_PATH'] ?? dirname(__DIR__) . '/storage/sessions';
if (!is_dir($sessionSavePath))
    mkdir($sessionSavePath, 0700, true);
session_save_path($sessionSavePath);
session_name($_ENV['SESSION_NAME'] ?? 'turnero_session');
session_set_cookie_params([
    'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 120) * 60,
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);

// --- Security Headers ---
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://sdk.mercadopago.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self' https://api.mercadopago.com;");

// --- Timezone ---
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Argentina/Buenos_Aires');

// --- Router ---
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$uri = rtrim($uri, '/') ?: '/';

// SUBFOLDER HANDLING
$basePath = $_ENV['APP_BASE_PATH'] ?? ''; // e.g. /turnero
$prefixes = array_filter([$basePath . '/public', $basePath, '/public']);

$targetUri = $uri;
foreach ($prefixes as $prefix) {
    if (!empty($prefix) && str_starts_with($uri, $prefix)) {
        $targetUri = substr($uri, strlen($prefix)) ?: '/';
        break;
    }
}

\App\Shared\Logging\AppLogger::debug("Routing attempt", [
    'method' => $method,
    'raw_uri' => $_SERVER['REQUEST_URI'],
    'target_uri' => $targetUri
]);

$routes = [
    'GET' => [
        '/' => [HomeController::class , 'index'],
        '/pago/exito' => [HomeController::class , 'success'],
        '/pago/fallo' => [HomeController::class , 'failure'],
        '/pago/pendiente' => [HomeController::class , 'pending'],
        '/cancelar' => [HomeController::class , 'cancel'],
        '/login' => [AuthController::class , 'loginForm'],
        '/logout' => [AuthController::class , 'logout'],
        '/api/available-slots' => [ApiController::class , 'availableSlots'],
        '/admin/dashboard' => [AdminController::class , 'dashboard'],
        '/admin/appointments' => [AdminController::class , 'appointments'],
        '/admin/services' => [AdminController::class , 'services'],
        '/admin/hours' => [AdminController::class , 'hours'],
        '/admin/settings' => [AdminController::class , 'settings'],
        '/admin/export-csv' => [AdminController::class , 'exportCsv'],
    ],
    'POST' => [
        '/login' => [AuthController::class , 'login'],
        '/api/appointments' => [ApiController::class , 'createAppointment'],
        '/api/cancel' => [ApiController::class , 'cancelAppointment'],
        '/webhook/mercadopago' => [WebhookController::class , 'mercadoPago'],
        '/admin/services/store' => [AdminController::class , 'storeService'],
        '/admin/services/update' => [AdminController::class , 'updateService'],
        '/admin/services/delete' => [AdminController::class , 'deleteService'],
        '/admin/hours/save' => [AdminController::class , 'saveHours'],
        '/admin/settings/save' => [AdminController::class , 'saveSettings'],
        '/cancelar' => [HomeController::class , 'cancel'],
    ],
];

try {
    if (isset($routes[$method][$targetUri])) {
        [$controllerClass, $action] = $routes[$method][$targetUri];
        (new $controllerClass())->$action();
    }
    else {
        http_response_code(404);
        $notFoundView = dirname(__DIR__) . '/views/public/404.php';
        if (file_exists($notFoundView)) {
            $debugUri = "$targetUri (Raw: $uri)";
            require $notFoundView;
        }
        else {
            echo "<h1>404 — Página no encontrada</h1><p>URI: $targetUri</p>";
        }
    }
}
catch (\Throwable $e) {
    \App\Shared\Logging\AppLogger::error('Unhandled exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    http_response_code(500);
    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        echo '<pre>' . htmlspecialchars("{$e->getMessage()}\n{$e->getFile()}:{$e->getLine()}") . '</pre>';
    }
    else {
        echo '<h1>Error interno</h1>';
    }
}