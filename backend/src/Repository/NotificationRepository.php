<?php

declare(strict_types=1);

namespace App\Repository;

use App\Contracts\NotificationRepositoryInterface;
use PDO;

final class NotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(string $secretKey, string $content): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO notifications (secret_key, content) VALUES (?, ?)'
        );
        $stmt->execute([$secretKey, $content]);

        return (int) $this->pdo->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, secret_key, content, created_at FROM notifications WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->normalizeRow($row);
    }

    public function findByUserId(int $userId, int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT n.id, n.secret_key, n.content, n.created_at
             FROM notifications n
             INNER JOIN users u ON u.secret_key = n.secret_key
             WHERE u.user_id = ?
             ORDER BY n.id DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $out[] = $this->normalizeRow($row);
            }
        }

        return $out;
    }

    public function countBySecretKey(string $secretKey): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM notifications WHERE secret_key = ?');
        $stmt->execute([$secretKey]);

        return (int) $stmt->fetchColumn();
    }

    public function deleteBySecretKey(string $secretKey): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM notifications WHERE secret_key = ?');
        $stmt->execute([$secretKey]);
    }

    /**
     * @param array<string, mixed> $row
     * @return array{id:int,secret_key:string,content:string,created_at:string}
     */
    private function normalizeRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'secret_key' => (string) $row['secret_key'],
            'content' => (string) $row['content'],
            'created_at' => (string) $row['created_at'],
        ];
    }
}
