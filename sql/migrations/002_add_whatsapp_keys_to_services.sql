-- Adds per-service WhatsApp Cloud API credentials
ALTER TABLE services
ADD COLUMN whatsapp_api_token VARCHAR(255) NULL AFTER mp_public_key,
ADD COLUMN whatsapp_phone_number_id VARCHAR(50) NULL AFTER whatsapp_api_token;
