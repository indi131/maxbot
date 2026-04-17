<?php

declare(strict_types=1);

namespace App\Repository;

use App\Contracts\NotificationRepositoryInterface;
use PDO;

final class NotificationRepository implements NotificationRepositoryInterface
{
    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
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

    public function listAll(int $limit, int $offset, ?string $secretKey = null): array
    {
        $sql = 'SELECT n.id, n.secret_key, n.content, n.created_at,
                       COALESCE(u.user_id, 0) AS user_id,
                       COALESCE(u.first_name, "") AS first_name,
                       COALESCE(u.username, "") AS username
                FROM notifications n
                LEFT JOIN users u ON u.secret_key = n.secret_key';
        if ($secretKey !== null && $secretKey !== '') {
            $sql .= ' WHERE n.secret_key = ?';
        }
        $sql .= ' ORDER BY n.id DESC LIMIT ? OFFSET ?';

        $stmt = $this->pdo->prepare($sql);
        $i = 1;
        if ($secretKey !== null && $secretKey !== '') {
            $stmt->bindValue($i++, $secretKey);
        }
        $stmt->bindValue($i++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($i, $offset, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $norm = $this->normalizeRow($row);
            $norm['user_id'] = (int) ($row['user_id'] ?? 0);
            $norm['first_name'] = (string) ($row['first_name'] ?? '');
            $norm['username'] = (string) ($row['username'] ?? '');
            $out[] = $norm;
        }

        return $out;
    }

    public function countAll(?string $secretKey = null): int
    {
        if ($secretKey === null || $secretKey === '') {
            return (int) $this->pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn();
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM notifications WHERE secret_key = ?');
        $stmt->execute([$secretKey]);

        return (int) $stmt->fetchColumn();
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
