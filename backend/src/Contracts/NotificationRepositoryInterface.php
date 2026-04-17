<?php

declare(strict_types=1);

namespace App\Contracts;

interface NotificationRepositoryInterface
{
    public function create(string $secretKey, string $content): int;

    /** @return array{id:int,secret_key:string,content:string,created_at:string}|null */
    public function findById(int $id): ?array;

    /**
     * @return list<array{id:int,secret_key:string,content:string,created_at:string}>
     */
    public function findByUserId(int $userId, int $limit = 20): array;

    public function countBySecretKey(string $secretKey): int;

    public function deleteBySecretKey(string $secretKey): void;

    /**
     * @return list<array{id:int,secret_key:string,content:string,created_at:string,user_id:int,first_name:string,username:string}>
     */
    public function listAll(int $limit, int $offset, ?string $secretKey = null): array;

    public function countAll(?string $secretKey = null): int;
}
