<?php

declare(strict_types=1);

namespace App\Messenger;

use App\Contracts\MessengerInterface;

final class MaxBot implements MessengerInterface
{
    private const BASE_URL = 'https://platform-api.max.ru';

    public function __construct(
        private readonly string $accessToken,
        private readonly string $webappBaseUrl,
    ) {
    }

    public function sendMessage(int $userId, string $text, array $buttons = []): void
    {
        $body = [
            'text' => $text,
            'format' => 'html',
            'notify' => true,
        ];

        if ($buttons !== []) {
            $body['attachments'] = [
                [
                    'type' => 'inline_keyboard',
                    'payload' => [
                        'buttons' => $buttons,
                    ],
                ],
            ];
        }

        $this->request('POST', '/messages?user_id=' . $userId, $body);
    }

    public function setWebhook(string $url, string $secret): bool
    {
        $payload = [
            'url' => $url,
            'update_types' => ['message_created', 'bot_started'],
            'secret' => $secret,
        ];
        $response = $this->request('POST', '/subscriptions', $payload);

        return ($response['success'] ?? false) === true;
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $body = []): array
    {
        $url = self::BASE_URL . $path;
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('curl_init failed');
        }

        $headers = [
            'Authorization: ' . $this->normalizeAuthHeader($this->accessToken),
            'Content-Type: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($body !== []) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
        }

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            throw new \RuntimeException("MAX API curl error: {$err}", $errno);
        }

        if ($raw === false || $raw === '') {
            throw new \RuntimeException('MAX API empty response, HTTP ' . $code);
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('MAX API invalid JSON: ' . $raw, 0, $e);
        }

        if ($code >= 400) {
            $msg = isset($decoded['message']) && is_string($decoded['message'])
                ? $decoded['message']
                : $raw;

            throw new \RuntimeException("MAX API HTTP {$code}: {$msg}");
        }

        return $decoded;
    }

    private function normalizeAuthHeader(string $token): string
    {
        if (stripos($token, 'Bearer ') === 0) {
            return $token;
        }

        return 'Bearer ' . $token;
    }

    public function getWebappBaseUrl(): string
    {
        return $this->webappBaseUrl;
    }
}
