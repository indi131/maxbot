<?php

declare(strict_types=1);

namespace App\Contracts;

interface MessengerInterface
{
    public function sendMessage(int $userId, string $text, array $buttons = []): void;

    public function setWebhook(string $url, string $secret): bool;
}
