#!/usr/bin/env php
<?php

/**
 * cron/send_reminders.php — CLI script for sending appointment reminders.
 * Run via cron every 15 minutes:
 *   * /15 * * * * /usr/bin/php /path/to/turnero/cron/send_reminders.php >> /path/to/logs/cron.log 2>&1
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Application\Services\ReminderService;
use App\Infrastructure\Repositories\AppointmentRepository;
use App\Infrastructure\Repositories\SettingsRepository;
use App\Shared\Logging\AppLogger;

$dotenv = Dotenv::createMutable(dirname(__DIR__));
$dotenv->safeLoad();
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Argentina/Buenos_Aires');

AppLogger::info('Cron: send_reminders started');

try {
    $reminderService = new ReminderService(
        new AppointmentRepository(),
        new SettingsRepository(),
        );

    $count = $reminderService->sendDueReminders();
    AppLogger::info("Cron: send_reminders finished", ['sent' => $count]);
    echo date('[Y-m-d H:i:s]') . " Reminders sent: {$count}\n";
}
catch (\Throwable $e) {
    AppLogger::error('Cron: send_reminders failed', ['error' => $e->getMessage()]);
    echo date('[Y-m-d H:i:s]') . " ERROR: {$e->getMessage()}\n";
    exit(1);
}