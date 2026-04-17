<?php

declare(strict_types=1);

namespace App\Messenger;

use App\Contracts\MessengerInterface;

final class MaxBot implements MessengerInterface
{
    /** @var string */
    private $accessToken;

    /** @var string */
    private $webappBaseUrl;

    /** @var string */
    private $apiBaseUrl;

    public function __construct(string $accessToken, string $webappBaseUrl, string $apiBaseUrl)
    {
        $this->accessToken = $accessToken;
        $this->webappBaseUrl = $webappBaseUrl;
        $this->apiBaseUrl = rtrim($apiBaseUrl, '/');
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
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $body = []): array
    {
        $url = $this->apiBaseUrl . $path;
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('curl_init failed');
        }

        $headers = [
            'Authorization: ' . $this->authorizationToken(),
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

    /** Токен для заголовка Authorization по документации MAX (без префикса Bearer). */
    private function authorizationToken(): string
    {
        $t = trim($this->accessToken);
        if (stripos($t, 'Bearer ') === 0) {
            return trim(substr($t, 7));
        }

        return $t;
    }

    public function getWebappBaseUrl(): string
    {
        return $this->webappBaseUrl;
    }
}
