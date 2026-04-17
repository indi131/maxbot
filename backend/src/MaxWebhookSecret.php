<?php

declare(strict_types=1);

namespace App;

/**
 * MAX принимает secret только в виде ^[a-zA-Z0-9_-]{5,256}$.
 * Для произвольного значения из .env в API и в заголовке вебхука используется одна и та же нормализация.
 */
final class MaxWebhookSecret
{
    private const PATTERN = '#^[a-zA-Z0-9_-]{5,256}$#';

    public static function forMaxApi(string $configured): string
    {
        $t = trim($configured);
        if ($t === '') {
            throw new \InvalidArgumentException('MAX_WEBHOOK_SECRET не задан');
        }

        if (preg_match(self::PATTERN, $t) === 1) {
            return $t;
        }

        return substr(hash('sha256', $t), 0, 64);
    }

    public static function headerMatches(string $configured, ?string $header): bool
    {
        if (trim($configured) === '') {
            return true;
        }

        if ($header === null || $header === '') {
            return false;
        }

        $expected = self::forMaxApi($configured);

        return hash_equals($expected, $header);
    }
}
