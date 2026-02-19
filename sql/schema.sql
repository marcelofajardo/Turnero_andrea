-- ============================================================
-- Turnero - Professional Appointment Management System
-- Schema Version: 1.0.0
-- Engine: InnoDB | Charset: utf8mb4_unicode_ci
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- Table: admin_users
-- Purpose: Admin authentication
-- ============================================================
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `username`      VARCHAR(50)     NOT NULL,
    `email`         VARCHAR(100)    NOT NULL,
    `password_hash` VARCHAR(255)    NOT NULL,
    `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
    `last_login`    DATETIME        DEFAULT NULL,
    `created_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_username` (`username`),
    UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin user accounts';

-- ============================================================
-- Table: services
-- Purpose: Bookable service catalog
-- ============================================================
CREATE TABLE IF NOT EXISTS `services` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`             VARCHAR(100)    NOT NULL,
    `slug`             VARCHAR(120)    NOT NULL,
    `description`      TEXT            DEFAULT NULL,
    `price`            DECIMAL(10,2)   NOT NULL DEFAULT '0.00',
    `duration_minutes` SMALLINT        NOT NULL DEFAULT 30 COMMENT 'Appointment duration in minutes',
    `color`            VARCHAR(7)      NOT NULL DEFAULT '#5AA9E6' COMMENT 'Calendar color hex',
    `is_active`        TINYINT(1)      NOT NULL DEFAULT 1,
    `sort_order`       SMALLINT        NOT NULL DEFAULT 0,
    `created_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_slug` (`slug`),
    KEY `idx_active`   (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Available services to book';

-- ============================================================
-- Table: business_hours
-- Purpose: Operating hours per day (supports multiple ranges)
-- ============================================================
CREATE TABLE IF NOT EXISTS `business_hours` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `service_id`  INT UNSIGNED  DEFAULT NULL COMMENT 'NULL = global rule',
    `day_of_week` TINYINT       NOT NULL COMMENT '1=Mon .. 7=Sun (ISO 8601)',
    `start_time`  TIME          NOT NULL,
    `end_time`    TIME          NOT NULL,
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_service_day` (`service_id`, `day_of_week`),
    CONSTRAINT `fk_hours_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Operating hours per day, per service or global';

-- ============================================================
-- Table: appointments
-- Purpose: Booking records — core table
-- ============================================================
CREATE TABLE IF NOT EXISTS `appointments` (
    `id`                     INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `service_id`             INT UNSIGNED  NOT NULL,
    `customer_name`          VARCHAR(100)  NOT NULL,
    `customer_phone`         VARCHAR(30)   NOT NULL,
    `customer_email`         VARCHAR(150)  DEFAULT NULL,
    `appointment_datetime`   DATETIME      NOT NULL,
    `end_datetime`           DATETIME      NOT NULL,
    `status`                 ENUM('pending','paid','cancelled','completed') NOT NULL DEFAULT 'pending',
    `notes`                  TEXT          DEFAULT NULL,
    `cancellation_token`     VARCHAR(64)   DEFAULT NULL,
    `reminder_sent`          TINYINT(1)   NOT NULL DEFAULT 0,
    `mercadopago_payment_id` VARCHAR(100)  DEFAULT NULL,
    `created_at`             TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`             TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    -- Prevent double-booking: same service at same start time
    UNIQUE KEY `uq_slot` (`service_id`, `appointment_datetime`),
    KEY `idx_status`         (`status`),
    KEY `idx_datetime`       (`appointment_datetime`),
    KEY `idx_token`          (`cancellation_token`),
    CONSTRAINT `fk_appt_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Appointment bookings';

-- ============================================================
-- Table: payments
-- Purpose: Full audit log of MercadoPago transactions
-- ============================================================
CREATE TABLE IF NOT EXISTS `payments` (
    `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `appointment_id` INT UNSIGNED  NOT NULL,
    `mp_payment_id`  VARCHAR(100)  NOT NULL,
    `mp_status`      VARCHAR(50)   NOT NULL,
    `mp_status_detail` VARCHAR(100) DEFAULT NULL,
    `amount`         DECIMAL(10,2) NOT NULL,
    `currency`       VARCHAR(3)    NOT NULL DEFAULT 'ARS',
    `raw_response`   JSON          DEFAULT NULL,
    `processed_at`   DATETIME      DEFAULT NULL,
    `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_mp_payment` (`mp_payment_id`) COMMENT 'Idempotency key',
    KEY `idx_appointment` (`appointment_id`),
    CONSTRAINT `fk_pay_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='MercadoPago payment log';

-- ============================================================
-- Table: settings
-- Purpose: Key-value store for runtime configuration
-- ============================================================
CREATE TABLE IF NOT EXISTS `settings` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `key_name`   VARCHAR(100)  NOT NULL,
    `value`      TEXT          DEFAULT NULL,
    `group`      VARCHAR(50)   NOT NULL DEFAULT 'general',
    `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_key` (`key_name`),
    KEY `idx_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Application runtime settings';

-- ============================================================
-- Table: failed_jobs
-- Purpose: CLI/cron retry queue for failed notifications
-- ============================================================
CREATE TABLE IF NOT EXISTS `failed_jobs` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `type`         VARCHAR(50)   NOT NULL COMMENT 'reminder, email, whatsapp',
    `payload`      JSON          NOT NULL,
    `attempts`     TINYINT       NOT NULL DEFAULT 0,
    `last_error`   TEXT          DEFAULT NULL,
    `failed_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `retry_after`  DATETIME      DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_type_retry` (`type`, `retry_after`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Failed background job queue';

-- ============================================================
-- Seed: Default Settings
-- ============================================================
INSERT INTO `settings` (`key_name`, `value`, `group`) VALUES
('appointment_duration_minutes', '30',                          'scheduling'),
('timezone',                     'America/Argentina/Buenos_Aires','scheduling'),
('reminder_hours_before',        '24',                          'notifications'),
('whatsapp_template_confirmation','Hola {name}! Tu turno para {service} el {date} a las {time} está confirmado. Cancelar: {cancel_url}', 'notifications'),
('whatsapp_template_reminder',   'Recordatorio: Mañana {date} a las {time} tenés turno para {service}.', 'notifications'),
('business_name',                'Mi Negocio',                  'general'),
('business_address',             '',                            'general'),
('cancellation_url_base',        '',                            'general'),
('mp_sandbox',                   'true',                        'payments'),
('maintenance_mode',             'false',                       'general')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- ============================================================
-- Seed: Admin User (password: admin123 — CHANGE IN PRODUCTION)
-- Generated hash: password_hash('admin123', PASSWORD_BCRYPT)
-- ============================================================
INSERT INTO `admin_users` (`username`, `email`, `password_hash`) VALUES
('admin', 'admin@turnero.local', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

-- ============================================================
-- Seed: Example Service
-- ============================================================
INSERT INTO `services` (`name`, `slug`, `description`, `price`, `duration_minutes`) VALUES
('Corte de Cabello', 'corte-cabello', 'Corte y peinado profesional', 2500.00, 30),
('Afeitado Clásico', 'afeitado-clasico', 'Afeitado con navaja profesional', 1800.00, 45)
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

-- ============================================================
-- Seed: Default Business Hours (Mon–Sat, 09:00–18:00)
-- ============================================================
INSERT INTO `business_hours` (`service_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(NULL, 1, '09:00:00', '13:00:00'), (NULL, 1, '15:00:00', '18:00:00'),
(NULL, 2, '09:00:00', '13:00:00'), (NULL, 2, '15:00:00', '18:00:00'),
(NULL, 3, '09:00:00', '13:00:00'), (NULL, 3, '15:00:00', '18:00:00'),
(NULL, 4, '09:00:00', '13:00:00'), (NULL, 4, '15:00:00', '18:00:00'),
(NULL, 5, '09:00:00', '13:00:00'), (NULL, 5, '15:00:00', '18:00:00'),
(NULL, 6, '09:00:00', '13:00:00');

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;
