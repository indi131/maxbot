<?php

declare(strict_types=1);

namespace App\Repository;

use App\Contracts\UserRepositoryInterface;
use PDO;

final class UserRepository implements UserRepositoryInterface
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findBySecretKey(string $key): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, secret_key, user_id, username, first_name, registered_at, last_activity
             FROM users WHERE secret_key = ? LIMIT 1'
        );
        $stmt->execute([$key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->normalizeRow($row);
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, secret_key, user_id, username, first_name, registered_at, last_activity
             FROM users WHERE user_id = ? LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->normalizeRow($row);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, secret_key, user_id, username, first_name, registered_at, last_activity
             FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->normalizeRow($row);
    }

    public function create(string $secretKey, int $userId, string $username, string $firstName): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (secret_key, user_id, username, first_name) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$secretKey, $userId, $username, $firstName]);
    }

    public function touchActivity(string $secretKey): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET last_activity = NOW() WHERE secret_key = ?');
        $stmt->execute([$secretKey]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function listAll(int $limit, int $offset): array
    {
        $sql = 'SELECT u.id, u.secret_key, u.user_id, u.username, u.first_name, u.registered_at, u.last_activity,
                       COUNT(n.id) AS notifications_count
                FROM users u
                LEFT JOIN notifications n ON n.secret_key = u.secret_key
                GROUP BY u.id, u.secret_key, u.user_id, u.username, u.first_name, u.registered_at, u.last_activity
                ORDER BY u.last_activity DESC
                LIMIT ? OFFSET ?';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $norm = $this->normalizeRow($row);
            $norm['notifications_count'] = (int) ($row['notifications_count'] ?? 0);
            $out[] = $norm;
        }

        return $out;
    }

    public function countAll(): int
    {
        $n = (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

        return $n;
    }

    /**
     * @param array<string, mixed> $row
     * @return array{id:int,secret_key:string,user_id:int,username:string,first_name:string,registered_at:string,last_activity:string}
     */
    private function normalizeRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'secret_key' => (string) $row['secret_key'],
            'user_id' => (int) $row['user_id'],
            'username' => (string) $row['username'],
            'first_name' => (string) $row['first_name'],
            'registered_at' => (string) $row['registered_at'],
            'last_activity' => (string) $row['last_activity'],
        ];
    }
}
