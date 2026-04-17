<?php

declare(strict_types=1);

namespace App\Service;

use App\Contracts\MessengerInterface;
use App\Contracts\NotificationRepositoryInterface;
use App\Contracts\UserRepositoryInterface;

final class AdminService
{
    private const PER_PAGE = 20;

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly NotificationRepositoryInterface $notifications,
        private readonly MessengerInterface $messenger,
    ) {
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
        } catch (\Throwable) {
            // игнорируем ошибку доставки — запись уже удалена
        }
    }
}
