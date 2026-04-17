<?php

declare(strict_types=1);

namespace App;

use Dotenv\Dotenv;

final class Config
{
    /** @var array<string, string> */
    private array $values;

    public function __construct(?string $basePath = null)
    {
        $basePath ??= dirname(__DIR__);
        if (is_file($basePath . '/.env')) {
            Dotenv::createImmutable($basePath)->safeLoad();
        }

        $this->values = [
            'DB_HOST' => $_ENV['DB_HOST'] ?? 'localhost',
            'DB_USER' => $_ENV['DB_USER'] ?? 'root',
            'DB_PASS' => $_ENV['DB_PASS'] ?? '',
            'DB_NAME' => $_ENV['DB_NAME'] ?? 'max_bot',
            'MAX_BOT_TOKEN' => $_ENV['MAX_BOT_TOKEN'] ?? '',
            'MAX_WEBHOOK_SECRET' => $_ENV['MAX_WEBHOOK_SECRET'] ?? '',
            'WEBAPP_URL' => rtrim($_ENV['WEBAPP_URL'] ?? '', '/'),
        ];
    }

    public function get(string $key, ?string $default = null): string
    {
        return $this->values[$key] ?? $default ?? '';
    }

    public function require(string $key): string
    {
        $v = $this->get($key);
        if ($v === '') {
            throw new \RuntimeException("Missing required config: {$key}");
        }

        return $v;
    }
}
