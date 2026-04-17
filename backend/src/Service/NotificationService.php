<?php

declare(strict_types=1);

namespace App\Service;

use App\Config;
use App\Contracts\MessengerInterface;
use App\Contracts\NotificationRepositoryInterface;
use App\Contracts\UserRepositoryInterface;

final class NotificationService
{
    /** @var UserRepositoryInterface */
    private $users;

    /** @var NotificationRepositoryInterface */
    private $notifications;

    /** @var MessengerInterface */
    private $messenger;

    /** @var Config */
    private $config;

    public function __construct(
        UserRepositoryInterface $users,
        NotificationRepositoryInterface $notifications,
        MessengerInterface $messenger,
        Config $config
    ) {
        $this->users = $users;
        $this->notifications = $notifications;
        $this->messenger = $messenger;
        $this->config = $config;
    }

    /**
     * @return array{status:string,id:int}
     */
    public function receive(string $secretKey, string $content): array
    {
        $secretKey = trim($secretKey);
        $content = trim($content);
        if ($secretKey === '' || $content === '') {
            throw new \InvalidArgumentException('password и data обязательны');
        }

        $user = $this->users->findBySecretKey($secretKey);
        if ($user === null) {
            throw new \RuntimeException('Неизвестный ключ доступа');
        }

        $id = $this->notifications->create($secretKey, $content);

        $webapp = $this->config->require('WEBAPP_URL');
        $userId = (int) $user['user_id'];
        $detailUrl = $webapp . '/detail.php?id=' . $id . '&user_id=' . $userId;

        $botUsername = ltrim($this->config->get('MAX_BOT_USERNAME'), '@');
        if ($botUsername !== '') {
            $payload = 'd' . $id . '_u' . $userId;
            $buttons = [
                [
                    [
                        'type' => 'link',
                        'text' => 'Открыть',
                        'url' => 'https://max.ru/' . rawurlencode($botUsername) . '?startapp=' . rawurlencode($payload),
                    ],
                ],
            ];
        } else {
            $buttons = [
                [
                    [
                        'type' => 'link',
                        'text' => 'Открыть',
                        'url' => $detailUrl,
                    ],
                ],
            ];
        }

        $source = $this->detectSource($content);
        $preview = $this->buildPreview($content, 160);
        $title = $source === 'VirtueMart'
            ? 'Новый заказ товара'
            : 'Новая заявка из формы';
        $text = '<b>' . $this->escapeHtml($title) . ' #' . $id . '</b>';
        if ($preview !== '') {
            $text .= "\n" . $this->escapeHtml($preview);
        }

        $this->messenger->sendMessage((int) $user['user_id'], $text, $buttons);

        $this->users->touchActivity($secretKey);

        return ['status' => 'ok', 'id' => $id];
    }

    private function detectSource(string $content): string
    {
        if (stripos($content, 'virtuemart') !== false || preg_match('/номер\s+заказа\s*:/iu', $content) === 1) {
            return 'VirtueMart';
        }

        return 'Сайт';
    }

    private function buildPreview(string $content, int $maxLen): string
    {
        $normalized = preg_replace('/<\s*br\s*\/?\s*>/iu', "\n", $content) ?? $content;
        $normalized = preg_replace('/<\s*\/p\s*>/iu', "\n", $normalized) ?? $normalized;
        $normalized = preg_replace('/<\s*p[^>]*>/iu', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/<\s*\/div\s*>/iu', "\n", $normalized) ?? $normalized;
        $normalized = preg_replace('/<\s*div[^>]*>/iu', '', $normalized) ?? $normalized;

        $plain = strip_tags($normalized);
        $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = str_replace(["\r\n", "\r"], "\n", $plain);
        $plain = preg_replace('/[ \t]+/u', ' ', $plain) ?? $plain;
        $plain = preg_replace("/\n{3,}/u", "\n\n", $plain) ?? $plain;
        $plain = trim($plain);
        if ($plain === '') {
            return '';
        }

        if (mb_strlen($plain) <= $maxLen) {
            return $plain;
        }

        return rtrim(mb_substr($plain, 0, $maxLen - 1)) . '…';
    }

    private function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
