<?php

declare(strict_types=1);

namespace App\Service;

use App\Config;
use App\Contracts\MessengerInterface;
use App\Contracts\NotificationRepositoryInterface;
use App\Contracts\UserRepositoryInterface;

final class NotificationService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly NotificationRepositoryInterface $notifications,
        private readonly MessengerInterface $messenger,
        private readonly Config $config,
    ) {
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
        $detailUrl = $webapp . '/detail.php?id=' . $id . '&user_id=' . (int) $user['user_id'];

        $buttons = [
            [
                [
                    'type' => 'link',
                    'text' => 'Открыть',
                    'url' => $detailUrl,
                ],
            ],
        ];

        $this->messenger->sendMessage(
            (int) $user['user_id'],
            '<b>Новая заявка на сайте!</b>',
            $buttons
        );

        $this->users->touchActivity($secretKey);

        return ['status' => 'ok', 'id' => $id];
    }
}
