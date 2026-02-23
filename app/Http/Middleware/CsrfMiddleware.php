<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Shared\Helpers\CsrfHelper;

/**
 * CsrfMiddleware — validates CSRF token on all state-changing POST requests.
 */
final class CsrfMiddleware
{
    public function handle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (!CsrfHelper::validate($token)) {
            http_response_code(403);
            die(json_encode(['success' => false, 'message' => 'CSRF token inválido.']));
        }
    }
}