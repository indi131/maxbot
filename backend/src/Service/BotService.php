<?php

declare(strict_types=1);

namespace App\Service;

use App\Contracts\MessengerInterface;
use App\Contracts\UserRepositoryInterface;

final class BotService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly MessengerInterface $messenger,
    ) {
    }

    /**
     * @param array<string, mixed> $update
     */
    public function handleUpdate(array $update): void
    {
        $type = isset($update['update_type']) && is_string($update['update_type'])
            ? $update['update_type']
            : '';

        match ($type) {
            'bot_started' => $this->onBotStarted($update),
            'message_created' => $this->onMessageCreated($update),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $update
     */
    private function onBotStarted(array $update): void
    {
        $userId = $this->extractUserIdFromBotStarted($update);
        if ($userId === null) {
            return;
        }

        $this->messenger->sendMessage(
            $userId,
            'Привет! Отправьте <b>секретный ключ доступа</b>, который вы получили для связи сайта с этим ботом.'
        );
    }

    /**
     * @param array<string, mixed> $update
     */
    private function onMessageCreated(array $update): void
    {
        $message = isset($update['message']) && is_array($update['message'])
            ? $update['message']
            : null;
        if ($message === null) {
            return;
        }

        $sender = isset($message['sender']) && is_array($message['sender'])
            ? $message['sender']
            : null;
        if ($sender === null) {
            return;
        }

        $userId = isset($sender['user_id']) ? (int) $sender['user_id'] : null;
        if ($userId === null || $userId === 0) {
            return;
        }

        if (! empty($sender['is_bot'])) {
            return;
        }

        $text = $this->extractMessageText($message);
        if ($text === null || trim($text) === '') {
            return;
        }

        $text = trim($text);
        $this->registerSecretKey($userId, $text, $sender);
    }

    /**
     * @param array<string, mixed> $update
     */
    private function extractUserIdFromBotStarted(array $update): ?int
    {
        if (isset($update['user']) && is_array($update['user']) && isset($update['user']['user_id'])) {
            return (int) $update['user']['user_id'];
        }

        if (isset($update['chat']) && is_array($update['chat'])) {
            $chat = $update['chat'];
            if (isset($chat['dialog_with_user']) && is_array($chat['dialog_with_user'])
                && isset($chat['dialog_with_user']['user_id'])) {
                return (int) $chat['dialog_with_user']['user_id'];
            }
            if (isset($chat['user_id'])) {
                return (int) $chat['user_id'];
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $message
     */
    private function extractMessageText(array $message): ?string
    {
        $body = isset($message['body']) && is_array($message['body'])
            ? $message['body']
            : null;
        if ($body === null) {
            return null;
        }

        if (isset($body['text']) && is_string($body['text'])) {
            return $body['text'];
        }

        if (isset($body['message']) && is_array($body['message']) && isset($body['message']['text'])) {
            return is_string($body['message']['text']) ? $body['message']['text'] : null;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $sender
     */
    private function registerSecretKey(int $userId, string $secretKey, array $sender): void
    {
        $username = isset($sender['username']) && is_string($sender['username']) ? $sender['username'] : '';
        $firstName = isset($sender['first_name']) && is_string($sender['first_name']) ? $sender['first_name'] : '';

        $existingByKey = $this->users->findBySecretKey($secretKey);
        if ($existingByKey !== null) {
            if ((int) $existingByKey['user_id'] === $userId) {
                $this->messenger->sendMessage($userId, 'Этот ключ уже привязан к вашему аккаунту.');
            } else {
                $this->messenger->sendMessage($userId, 'Такой ключ уже зарегистрирован другим пользователем.');
            }

            return;
        }

        $existingUser = $this->users->findByUserId($userId);
        if ($existingUser !== null) {
            $this->messenger->sendMessage($userId, 'Вы уже зарегистрированы. Для смены ключа обратитесь к администратору.');

            return;
        }

        $this->users->create($secretKey, $userId, $username, $firstName);
        $this->messenger->sendMessage($userId, 'Ключ доступа сохранён. Уведомления с сайта будут приходить сюда.');
    }
}
