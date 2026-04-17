<?php

declare(strict_types=1);

namespace App\Service;

use App\MaxWebhookSecret;
use App\Contracts\MessengerInterface;
use App\Contracts\NotificationRepositoryInterface;
use App\Contracts\UserRepositoryInterface;

final class AdminService
{
    private const DEFAULT_PER_PAGE = 20;
    private const MAX_PER_PAGE = 100;

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
    public function getClients(int $page, int $perPage = self::DEFAULT_PER_PAGE): array
    {
        $perPage = $this->normalizePerPage($perPage);
        $page = max(1, $page);
        $total = $this->users->countAll();
        $pages = max(1, (int) ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }

        $offset = ($page - 1) * $perPage;
        $items = $this->users->listAll($perPage, $offset);

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

    /**
     * @return array{items:list<array<string,mixed>>,page:int,pages:int,total:int}
     */
    public function getNotifications(int $page, ?int $clientId = null, int $perPage = self::DEFAULT_PER_PAGE): array
    {
        $perPage = $this->normalizePerPage($perPage);
        $page = max(1, $page);
        $secretKey = null;

        if ($clientId !== null && $clientId > 0) {
            $client = $this->users->findById($clientId);
            if ($client === null) {
                throw new \RuntimeException('Клиент не найден');
            }
            $secretKey = (string) $client['secret_key'];
        }

        $total = $this->notifications->countAll($secretKey);
        $pages = max(1, (int) ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }

        $offset = ($page - 1) * $perPage;
        $items = $this->notifications->listAll($perPage, $offset, $secretKey);

        return [
            'items' => $items,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ];
    }

    public function createBackupJson(): string
    {
        $users = [];
        $offset = 0;
        do {
            $chunk = $this->users->listAll(1000, $offset);
            foreach ($chunk as $row) {
                $users[] = $row;
            }
            $offset += 1000;
        } while ($chunk !== []);

        $notifications = [];
        $offset = 0;
        do {
            $chunk = $this->notifications->listAll(1000, $offset);
            foreach ($chunk as $row) {
                $notifications[] = $row;
            }
            $offset += 1000;
        } while ($chunk !== []);

        $payload = [
            'generated_at' => gmdate('c'),
            'users' => $users,
            'notifications' => $notifications,
            'meta' => [
                'users_total' => count($users),
                'notifications_total' => count($notifications),
            ],
        ];

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    /**
     * @return array{sent:int,failed:int}
     */
    public function sendBroadcast(string $message, ?int $clientId = null): array
    {
        $message = trim($message);
        if ($message === '') {
            throw new \InvalidArgumentException('Текст рассылки не должен быть пустым');
        }
        // В MAX переносы через <br> часто не рендерятся, конвертируем в реальный перенос строки.
        $message = preg_replace('/<\s*br\s*\/?\s*>/iu', "\n", $message) ?? $message;

        $targets = [];
        if ($clientId !== null && $clientId > 0) {
            $user = $this->users->findById($clientId);
            if ($user === null) {
                throw new \RuntimeException('Клиент не найден');
            }
            $targets[] = $user;
        } else {
            $offset = 0;
            do {
                $chunk = $this->users->listAll(500, $offset);
                foreach ($chunk as $row) {
                    $targets[] = $row;
                }
                $offset += 500;
            } while ($chunk !== []);
        }

        $sent = 0;
        $failed = 0;
        foreach ($targets as $user) {
            try {
                $this->messenger->sendMessage((int) $user['user_id'], $message);
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    private function normalizePerPage(int $perPage): int
    {
        if ($perPage < 1) {
            return self::DEFAULT_PER_PAGE;
        }

        return min(self::MAX_PER_PAGE, $perPage);
    }
}