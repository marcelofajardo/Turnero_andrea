<?php

declare(strict_types=1);

namespace App\Http\Controllers;

/**
 * BaseController — shared utilities for all controllers.
 * Controllers are thin orchestrators — no business logic here.
 */
abstract class BaseController
{
    /**
     * Render a view file from the /views directory.
     * @param string $view  Dot-notation path relative to /views (e.g. 'public.home')
     * @param array  $data  Variables to extract into the view scope
     */
    protected function view(string $view, array $data = []): void
    {
        $path = str_replace('.', DIRECTORY_SEPARATOR, $view);
        $file = dirname(__DIR__, 3) . "/views/{$path}.php";

        if (!file_exists($file)) {
            throw new \RuntimeException("View not found: {$file}");
        }

        extract($data, EXTR_SKIP);
        require $file;
    }

    /**
     * Send a JSON response and terminate.
     */
    protected function json(bool $success, string $message, mixed $data = null, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Redirect and terminate.
     */
    protected function redirect(string $url): never
    {
        $base = $_ENV['APP_BASE_PATH'] ?? '';

        // If it's a relative path starting with /, prepend the base path
        if ($base && str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            $url = $base . $url;
        }

        header("Location: {$url}");
        exit;
    }

    /** Returns raw POST body as array (for API endpoints receiving JSON) */
    protected function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }
}