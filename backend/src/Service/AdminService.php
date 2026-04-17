<?php

declare(strict_types=1);

namespace App\Service;

use App\MaxWebhookSecret;
use App\Contracts\MessengerInterface;
use App\Contracts\NotificationRepositoryInterface;
use App\Contracts\UserRepositoryInterface;

final class AdminService
{
    private const PER_PAGE = 20;

    /** @var UserRepositoryInterface */
    private $users;

    /** @var NotificationRepositoryInterface */
    private $notifications;

    /** @var MessengerInterface */
    private $messenger;

    public function __construct(
        UserRepositoryInterface $users,
        NotificationRepositoryInterface $notifications,
        MessengerInterface $messenger
    ) {
        $this->users = $users;
        $this->notifications = $notifications;
        $this->messenger = $messenger;
    }

    /**
     * @return array{items:list<array<string,mixed>>,page:int,pages:int,total:int}
     */
    public function getClients(int $page): array
    {
        $page = max(1, $page);
        $total = $this->users->countAll();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        if ($page > $pages) {
            $page = $pages;
        }

        $offset = ($page - 1) * self::PER_PAGE;
        $items = $this->users->listAll(self::PER_PAGE, $offset);

        return [
            'items' => $items,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ];
    }

    public function deleteClient(int $id): void
    {
        $user = $this->users->findById($id);
        if ($user === null) {
            throw new \RuntimeException('Клиент не найден');
        }

        $userId = (int) $user['user_id'];
        $secret = (string) $user['secret_key'];

        $this->notifications->deleteBySecretKey($secret);
        $this->users->delete($id);

        try {
            $this->messenger->sendMessage(
                $userId,
                'Ваша регистрация в сервисе уведомлений удалена администратором.'
            );
        } catch (\Throwable $e) {
            // игнорируем ошибку доставки — запись уже удалена
        }
    }

    public function configureWebhook(string $backendBaseUrl, string $secret): bool
    {
        $base = trim($backendBaseUrl);
        if ($base === '') {
            throw new \InvalidArgumentException('Укажите URL бэкенда');
        }

        if (! preg_match('#^https?://#i', $base)) {
            $base = 'https://' . $base;
        }

        $parts = parse_url($base);
        if (! is_array($parts) || empty($parts['host'])) {
            throw new \InvalidArgumentException('Некорректный URL бэкенда');
        }

        $scheme = isset($parts['scheme']) ? strtolower((string) $parts['scheme']) : 'https';
        if ($scheme !== 'https') {
            throw new \InvalidArgumentException('Webhook должен быть на https');
        }

        $host = (string) $parts['host'];
        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $path = isset($parts['path']) ? rtrim((string) $parts['path'], '/') : '';

        $webhookUrl = $scheme . '://' . $host . $port . $path . '/webhook.php';

        return $this->messenger->setWebhook($webhookUrl, MaxWebhookSecret::forMaxApi($secret));
    }
}