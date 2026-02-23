<?php

declare(strict_types=1);

namespace App\Infrastructure\Gateways;

use App\Shared\Logging\AppLogger;

/**
 * MetaCloudApiGateway - Implementation for Meta WhatsApp Cloud API.
 */
final class MetaCloudApiGateway implements WhatsAppGateway
{
    private string $baseUrl = 'https://graph.facebook.com/v18.0';

    public function __construct(
        private readonly string $accessToken,
        private readonly string $phoneNumberId
    ) {}

    /**
     * @inheritDoc
     */
    public function sendMessage(string $to, string $message): bool
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'text',
            'text'              => ['body' => $message]
        ];

        return $this->sendRequest($payload);
    }

    /**
     * @inheritDoc
     */
    public function sendTemplate(string $to, string $templateName, string $languageCode, array $components = []): bool
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'template',
            'template'          => [
                'name'     => $templateName,
                'language' => ['code' => $languageCode],
                'components' => $components
            ]
        ];

        return $this->sendRequest($payload);
    }

    /**
     * Sends the HTTP request to Meta API.
     */
    private function sendRequest(array $payload): bool
    {
        if (empty($this->accessToken) || empty($this->phoneNumberId)) {
            AppLogger::warning("WhatsApp API credentials missing, skipping request.");
            return false;
        }

        $url = "{$this->baseUrl}/{$this->phoneNumberId}/messages";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode >= 400) {
            AppLogger::error("WhatsApp API Error", [
                'status'   => $httpCode,
                'response' => $response,
                'error'    => $error,
                'payload'  => $payload
            ]);
            return false;
        }

        AppLogger::info("WhatsApp message sent successfully", ['response' => json_decode($response, true)]);
        return true;
    }
}