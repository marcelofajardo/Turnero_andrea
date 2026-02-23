<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Infrastructure\Repositories\ServiceRepository;
use App\Shared\Logging\AppLogger;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$master = $_ENV['APP_MASTER_KEY'] ?? null;
if (empty($master)) {
    echo "APP_MASTER_KEY is not set in .env. Aborting.\n";
    exit(1);
}

// Ensure log path is writable for this script (avoid storage permission issues)
if (empty($_ENV['LOG_PATH'])) {
    $_ENV['LOG_PATH'] = '/tmp/turnero_app.log';
}
if (!file_exists($_ENV['LOG_PATH'])) {
    @touch($_ENV['LOG_PATH']);
    @chmod($_ENV['LOG_PATH'], 0664);
}

$repo = new ServiceRepository();
$services = $repo->findAll(false);

$count = 0;
foreach ($services as $svc) {
    try {
        // Re-save to trigger encryption in repository
        $repo->update($svc);
        $count++;
    } catch (Throwable $e) {
        AppLogger::error('Failed to re-encrypt service token', ['service_id' => $svc->getId(), 'error' => $e->getMessage()]);
        echo "Failed to update service {$svc->getId()}: {$e->getMessage()}\n";
    }
}

echo "Re-encrypted tokens for {$count} services.\n";

