-- Migration: Add per-service MercadoPago sandbox flag
ALTER TABLE `services`
  ADD COLUMN `mp_sandbox` TINYINT(1) NOT NULL DEFAULT 0 AFTER `mp_public_key`;
