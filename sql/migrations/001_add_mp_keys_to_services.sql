-- Migration: Add Multi-Gateway MercadoPago support to services table
ALTER TABLE `services` 
ADD COLUMN `mp_access_token` VARCHAR(255) DEFAULT NULL AFTER `sort_order`,
ADD COLUMN `mp_public_key` VARCHAR(255) DEFAULT NULL AFTER `mp_access_token`;
