<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

/**
 * CsrfHelper — generate and validate CSRF tokens stored in session.
 */
final class CsrfHelper
{
    private const SESSION_KEY = '_csrf_token';

    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function validate(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $stored = $_SESSION[self::SESSION_KEY] ?? '';
        return hash_equals($stored, $token);
    }

    public static function inputHtml(): string
    {
        $token = self::getToken();
        return "<input type=\"hidden\" name=\"_csrf\" value=\"{$token}\">";
    }
}