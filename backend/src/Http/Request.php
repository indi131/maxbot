<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    /** @var array<string, mixed>|null */
    private static ?array $cachedJson = null;

    public function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (! isset($_SERVER[$key])) {
            return null;
        }

        $v = $_SERVER[$key];

        return is_string($v) ? $v : null;
    }

    public function method(): string
    {
        return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    /**
     * @return array<string, mixed>
     */
    public function getJson(): array
    {
        if (self::$cachedJson !== null) {
            return self::$cachedJson;
        }

        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            throw new \InvalidArgumentException('Пустое тело запроса');
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException('Некорректный JSON', 0, $e);
        }

        if (! is_array($data)) {
            throw new \InvalidArgumentException('JSON должен быть объектом');
        }

        /** @var array<string, mixed> $data */
        self::$cachedJson = $data;

        return self::$cachedJson;
    }
}
