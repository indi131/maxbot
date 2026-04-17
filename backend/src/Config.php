<?php

declare(strict_types=1);

namespace App;

final class Config
{
    /** @var array<string, string> */
    private $values;

    public function __construct(?string $basePath = null)
    {
        $basePath = $basePath ?? dirname(__DIR__);
        $envFile = $basePath . DIRECTORY_SEPARATOR . '.env';
        if (is_file($envFile) && is_readable($envFile)) {
            self::loadEnvFile($envFile);
        }

        $this->values = [
            'DB_HOST' => $_ENV['DB_HOST'] ?? 'localhost',
            'DB_USER' => $_ENV['DB_USER'] ?? 'root',
            'DB_PASS' => $_ENV['DB_PASS'] ?? '',
            'DB_NAME' => $_ENV['DB_NAME'] ?? 'max_bot',
            'MAX_BOT_TOKEN' => $_ENV['MAX_BOT_TOKEN'] ?? '',
            'MAX_WEBHOOK_SECRET' => $_ENV['MAX_WEBHOOK_SECRET'] ?? '',
            'WEBAPP_URL' => rtrim($_ENV['WEBAPP_URL'] ?? '', '/'),
            'MAX_API_BASE_URL' => rtrim($_ENV['MAX_API_BASE_URL'] ?? 'https://platform-api.max.ru', '/'),
            'MAX_BOT_USERNAME' => trim($_ENV['MAX_BOT_USERNAME'] ?? ''),
        ];
    }

    private static function loadEnvFile(string $path): void
    {
        $raw = @file($path, FILE_IGNORE_NEW_LINES);
        if ($raw === false) {
            return;
        }
        foreach ($raw as $line) {
            $line = trim($line);
            if ($line === '' || (isset($line[0]) && $line[0] === '#')) {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if ($name === '') {
                continue;
            }
            if (strlen($value) >= 2 && $value[0] === '"' && substr($value, -1) === '"') {
                $value = stripcslashes(substr($value, 1, -1));
            } elseif (strlen($value) >= 2 && $value[0] === "'" && substr($value, -1) === "'") {
                $value = stripcslashes(substr($value, 1, -1));
            }
            $_ENV[$name] = $value;
            putenv($name . '=' . $value);
        }
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
