<?php

declare(strict_types=1);

namespace App\Infrastructure\Gateways;

/**
 * WhatsAppGateway - Interface for sending messages via WhatsApp.
 */
interface WhatsAppGateway
{
    /**
     * Sends a message to a phone number.
     * 
     * @param string $to Phone number in international format (e.g., "54911...")
     * @param string $message The text message to send
     * @return bool True on success
     * @throws \Exception If sending fails
     */
    public function sendMessage(string $to, string $message): bool;

    /**
     * Sends a template-based message (Official API).
     * 
     * @param string $to Phone number
     * @param string $templateName Template name in Meta
     * @param string $languageCode e.g., "es_AR"
     * @param array $components Variables for the template
     * @return bool
     */
    public function sendTemplate(string $to, string $templateName, string $languageCode, array $components = []): bool;
}