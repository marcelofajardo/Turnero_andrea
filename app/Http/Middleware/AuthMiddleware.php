<?php

declare(strict_types=1);

namespace App\Http\Middleware;

/**
 * AuthMiddleware — ensures the request comes from an authenticated admin.
 * Blocks access and redirects to /login on failure.
 */
final class AuthMiddleware
{
    public function handle(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['admin_id'])) {
            header('Location: /login');
            exit;
        }
    }
}