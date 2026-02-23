<?php
// scripts/test_create_preference.php
// Attempts to create a MercadoPago preference using the service's token (service id configurable)

require_once __DIR__ . "/../vendor/autoload.php";

// Load .env minimally
$env = parse_ini_file(__DIR__ . '/../.env', false, INI_SCANNER_RAW) ?: [];
foreach ($env as $k => $v) {
    if (!isset($_ENV[$k])) $_ENV[$k] = trim($v, "\"'");
}

// Respect existing LOG_PATH if set; otherwise prefer a writable temp log path for CLI runs
if (empty($_ENV['LOG_PATH'])) {
    $_ENV['LOG_PATH'] = sys_get_temp_dir() . '/turnero_app.log';
}

use App\Infrastructure\Repositories\ServiceRepository;
use App\Application\Services\MercadoPagoService;

$serviceId = $argv[1] ?? 3; // default service 3

$serviceRepo = new ServiceRepository();
$svc = $serviceRepo->findById((int)$serviceId);
if (!$svc) {
    echo "Service {$serviceId} not found\n";
    exit(1);
}

$mp = new MercadoPagoService(new \App\Infrastructure\Repositories\AppointmentRepository(), new \App\Application\Services\AppointmentService(new \App\Infrastructure\Repositories\AppointmentRepository(), $serviceRepo, new \App\Infrastructure\Repositories\SettingsRepository(), new \App\Application\Services\AppointmentAvailabilityService(new \App\Infrastructure\Repositories\AppointmentRepository(), new \App\Infrastructure\Repositories\SettingsRepository())));

try {
    $res = $mp->createPreference(99999, $svc->getName() . ' (Prueba)', $svc->getPrice(), $svc->getId());
    echo "Preference created:\n";
    print_r($res);
} catch (Throwable $e) {
    echo "Failed to create preference: " . $e->getMessage() . PHP_EOL;
    if (method_exists($e, 'getApiResponse')) {
        $api = $e->getApiResponse();
        if ($api) print_r($api->getContent());
    }
}
