<?php

declare(strict_types=1);

namespace App\Shared\Logging;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Level;

/**
 * Static facade over Monolog — structured JSON logging.
 * Usage: AppLogger::info('Message', ['key' => 'value']);
 */
final class AppLogger
{
    private static ?Logger $instance = null;

    private static function getInstance(): Logger
    {
        if (self::$instance === null) {
            $logPath = $_ENV['LOG_PATH'] ?? 'storage/logs/app.log';
            $logLevel = match (strtolower($_ENV['LOG_LEVEL'] ?? 'debug')) {
                    'error' => Level::Error,
                    'warning' => Level::Warning,
                    'info' => Level::Info,
                    default => Level::Debug,
                };

            $handler = new StreamHandler($logPath, $logLevel);
            $formatter = new JsonFormatter();
            $handler->setFormatter($formatter);

            self::$instance = new Logger('turnero');
            self::$instance->pushHandler($handler);
        }

        return self::$instance;
    }

    public static function debug(string $message, array $context = []): void
    {
        self::getInstance()->debug($message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::getInstance()->info($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::getInstance()->warning($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::getInstance()->error($message, $context);
    }
}