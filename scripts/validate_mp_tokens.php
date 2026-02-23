<?php
// scripts/validate_mp_tokens.php
// Verifies Mercado Pago tokens for all services, auto-corrects mp_sandbox when prefix mismatches,
// and pings the MP API to check token validity. Logs results to storage/logs/mp_tokens_check.log

require_once __DIR__ . "/../vendor/autoload.php";

use App\Infrastructure\Repositories\ServiceRepository;
use App\Shared\Security\Encryptor;

// Load minimal env
$env = parse_ini_file(__DIR__ . '/../.env', false, INI_SCANNER_RAW) ?: [];
foreach ($env as $k => $v) {
    if (!isset($_ENV[$k])) $_ENV[$k] = trim($v, "\"'");
}

// Prefer a writable temp log path for CLI runs (avoid web server-owned storage permission issues)
$logPath = sys_get_temp_dir() . '/mp_tokens_check.log';
$now = (new DateTimeImmutable('now'))->format('c');

$log = function($msg) use ($logPath, $now) {
    $entry = "[$now] " . $msg . PHP_EOL;
    $res = @file_put_contents($logPath, $entry, FILE_APPEND);
    if ($res === false) {
        // fallback to stderr if write fails
        fwrite(STDERR, $entry);
    }
};

$serviceRepo = new ServiceRepository();

// We'll fetch all services
$pdo = new ReflectionProperty($serviceRepo, 'pdo');
$pdo->setAccessible(true);
$pdoInstance = $pdo->getValue($serviceRepo);

$stmt = $pdoInstance->query('SELECT * FROM services');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "No services found\n";
    exit(0);
}

foreach ($rows as $row) {
    $id = (int)$row['id'];
    $name = $row['name'];
    $enc = $row['mp_access_token'] ?? null;
    $mpSandboxDb = (int)($row['mp_sandbox'] ?? 0);

    if (empty($enc)) {
        echo "Service {$id} ({$name}): no token configured\n";
        $log("Service {$id} ({$name}): no token configured");
        continue;
    }

    $token = Encryptor::decrypt($enc);
    if ($token === null) {
        echo "Service {$id} ({$name}): failed to decrypt token\n";
        $log("Service {$id} ({$name}): failed to decrypt token");
        continue;
    }

    // Determine expected sandbox from token prefix
    $expectedSandbox = null;
    if (str_starts_with($token, 'TEST-')) {
        $expectedSandbox = 1;
    } elseif (str_starts_with($token, 'APP_USR-') || str_starts_with($token, 'APP-')) {
        $expectedSandbox = 0;
    }

    if ($expectedSandbox !== null && $expectedSandbox !== $mpSandboxDb) {
        // Auto-correct DB flag
        $updateStmt = $pdoInstance->prepare('UPDATE services SET mp_sandbox = :s WHERE id = :id');
        $updateStmt->execute([':s' => $expectedSandbox, ':id' => $id]);
        echo "Service {$id} ({$name}): mp_sandbox auto-corrected from {$mpSandboxDb} to {$expectedSandbox}\n";
        $log("Service {$id} ({$name}): mp_sandbox auto-corrected from {$mpSandboxDb} to {$expectedSandbox}");
        $mpSandboxDb = $expectedSandbox;
    }

    // Ping Mercado Pago to validate the token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/users/me');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ]);
    $body = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        echo "Service {$id} ({$name}): curl error: {$err}\n";
        $log("Service {$id} ({$name}): curl error: {$err}");
        continue;
    }

    if ($http === 200) {
        echo "Service {$id} ({$name}): token VALID (HTTP 200)\n";
        $log("Service {$id} ({$name}): token VALID (HTTP 200)");
    } else {
        $info = $body ?: 'no body';
        echo "Service {$id} ({$name}): token INVALID (HTTP {$http}) - {$info}\n";
        $log("Service {$id} ({$name}): token INVALID (HTTP {$http}) - {$info}");
    }
}

echo "Done. Log: {$logPath}\n";
