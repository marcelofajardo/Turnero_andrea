# MercadoPago per-service configuration

This project supports per-service MercadoPago configuration (access token, public key, and sandbox mode).

## Overview
- Tokens are stored in the `services` table: `mp_access_token`, `mp_public_key` and `mp_sandbox`.
- Tokens are encrypted in the database using `APP_MASTER_KEY` from `.env` (AES-256-GCM).
- Admin UI: `/admin/services` allows setting the Access Token, Public Key, and the `Usar Sandbox` checkbox per service.
- No global MP secrets are required in `.env` when configuring per-service credentials.

## Required env variable
- `APP_MASTER_KEY`: a secret used to encrypt tokens at rest. Example (use a secure random value):

  APP_MASTER_KEY=replace_with_a_strong_secret_here

If `APP_MASTER_KEY` is not set, tokens will be stored as plaintext and a warning will be logged.

## Database migration
A migration file was added: `sql/migrations/003_add_mp_sandbox_to_services.sql`.
Run your migration process or execute the SQL manually to add the `mp_sandbox` column to the `services` table.

Example:

```sql
ALTER TABLE `services`
  ADD COLUMN `mp_sandbox` TINYINT(1) NOT NULL DEFAULT 0 AFTER `mp_public_key`;
```

## How it works
- When creating a payment preference, the code will use the service-specific access token if present and will initialize the MercadoPago SDK in sandbox or production depending on the `mp_sandbox` flag for that service.
- Webhooks include `?sid={serviceId}` in `notification_url`, so incoming webhooks are re-initialized using the service token and sandbox flag before fetching the payment via the SDK.

## Security recommendations
- Set a strong `APP_MASTER_KEY` and keep it out of source control.
- Rotate access tokens periodically and update them via the admin UI.
- Back up the database and the `APP_MASTER_KEY` — if you lose the key, encrypted tokens cannot be recovered.

## Notes for developers
- Encryption is implemented in `app/Shared/Security/Encryptor.php`.
- Service persistence changes are in `app/Infrastructure/Repositories/ServiceRepository.php`.
- SDK initialization and per-service sandbox behavior are in `app/Application/Services/MercadoPagoService.php`.
- Admin UI changes are in `views/admin/services.php` and frontend JS in `public/assets/js/admin.js`.

*** End of document
