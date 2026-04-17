<?php

declare(strict_types=1);

namespace App\Database;

use App\Config;
use PDO;
use PDOException;

final class Connection
{
    private static ?PDO $pdo = null;

    public static function getInstance(Config $config): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = $config->get('DB_HOST');
        $name = $config->get('DB_NAME');
        $user = $config->get('DB_USER');
        $pass = $config->get('DB_PASS');

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name);

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }

        return self::$pdo;
    }

    /** @internal for tests */
    public static function reset(): void
    {
        self::$pdo = null;
    }
}
