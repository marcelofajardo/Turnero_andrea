<?php

// Lightweight script to encrypt tokens in `services` table directly via PDO.
// Does NOT bootstrap the app to avoid logging/permission issues.

$root = dirname(__DIR__);
$envFile = $root . '/.env';
if (!file_exists($envFile)) {
    echo ".env not found at {$envFile}\n";
    exit(1);
}

$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (!str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $k = trim($k);
    $v = trim($v);
    $v = trim($v, "\"'");
    $env[$k] = $v;
}

$master = $env['APP_MASTER_KEY'] ?? null;
if (empty($master)) {
    echo "APP_MASTER_KEY not set in .env — aborting.\n";
    exit(1);
}

$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$db   = $env['DB_DATABASE'] ?? 'turnero_db';
$user = $env['DB_USERNAME'] ?? 'root';
$pass = $env['DB_PASSWORD'] ?? '';

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo "DB connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

function encrypt_payload(string $master, string $plaintext): string {
    $cipher = 'aes-256-gcm';
    $key = hash('sha256', $master, true);
    $ivLen = openssl_cipher_iv_length($cipher);
    $iv = random_bytes($ivLen);
    $tag = '';
    $ct = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
    return base64_encode($iv . $tag . $ct);
}

function looks_encrypted(?string $val): bool {
    if ($val === null || $val === '') return false;
    $data = base64_decode($val, true);
    if ($data === false) return false;
    $ivLen = openssl_cipher_iv_length('aes-256-gcm');
    $tagLen = 16;
    return strlen($data) >= ($ivLen + $tagLen + 1);
}

// Fetch services
$stmt = $pdo->query('SELECT id, mp_access_token, whatsapp_api_token FROM services');
$rows = $stmt->fetchAll();

$updated = 0;
foreach ($rows as $r) {
    $id = (int)$r['id'];
    $mp = $r['mp_access_token'];
    $wa = $r['whatsapp_api_token'];

    $toUpdate = [];

    if (!empty($mp) && !looks_encrypted($mp)) {
        $toUpdate['mp_access_token'] = encrypt_payload($master, $mp);
    }
    if (!empty($wa) && !looks_encrypted($wa)) {
        $toUpdate['whatsapp_api_token'] = encrypt_payload($master, $wa);
    }

    if (!empty($toUpdate)) {
        $sets = [];
        $params = [':id' => $id];
        foreach ($toUpdate as $col => $val) {
            $sets[] = "{$col} = :{$col}";
            $params[":{$col}"] = $val;
        }
        $sql = 'UPDATE services SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $u = $pdo->prepare($sql);
        $u->execute($params);
        $updated++;
        echo "Updated service {$id}\n";
    }
}

echo "Re-encryption complete. {$updated} rows updated.\n";
