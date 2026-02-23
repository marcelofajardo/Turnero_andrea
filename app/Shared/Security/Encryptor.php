<?php

declare(strict_types=1);

namespace App\Shared\Security;

use App\Shared\Logging\AppLogger;

final class Encryptor
{
    private const CIPHER = 'aes-256-gcm';

    private static function getKey(): ?string
    {
        $master = $_ENV['APP_MASTER_KEY'] ?? null;
        if (empty($master)) {
            AppLogger::warning('APP_MASTER_KEY not set — tokens will be stored in plaintext.');
            return null;
        }

        return hash('sha256', $master, true);
    }

    public static function encrypt(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '') {
            return null;
        }

        $key = self::getKey();
        if ($key === null) {
            return $plaintext;
        }

        $ivLen = openssl_cipher_iv_length(self::CIPHER);
        $iv = random_bytes($ivLen);
        $tag = '';

        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false) {
            AppLogger::error('Encryptor: openssl_encrypt failed');
            return null;
        }

        // Store iv + tag + ciphertext
        return base64_encode($iv . $tag . $ciphertext);
    }

    public static function decrypt(?string $payload): ?string
    {
        if ($payload === null || $payload === '') {
            return null;
        }

        $key = self::getKey();
        if ($key === null) {
            return $payload;
        }

        $data = base64_decode($payload, true);
        if ($data === false) {
            AppLogger::warning('Encryptor: payload is not base64, returning raw');
            return $payload;
        }

        $ivLen = openssl_cipher_iv_length(self::CIPHER);
        $tagLen = 16; // AES-GCM tag length

        if (strlen($data) < ($ivLen + $tagLen)) {
            AppLogger::warning('Encryptor: payload too short');
            return null;
        }

        $iv = substr($data, 0, $ivLen);
        $tag = substr($data, $ivLen, $tagLen);
        $ciphertext = substr($data, $ivLen + $tagLen);

        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plaintext === false) {
            AppLogger::warning('Encryptor: decryption failed');
            return null;
        }

        return $plaintext;
    }
}
