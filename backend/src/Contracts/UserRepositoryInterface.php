<?php

declare(strict_types=1);

namespace App\Contracts;

interface UserRepositoryInterface
{
    /** @return array{id:int,secret_key:string,user_id:int,username:string,first_name:string,registered_at:string,last_activity:string}|null */
    public function findBySecretKey(string $key): ?array;

    /** @return array{id:int,secret_key:string,user_id:int,username:string,first_name:string,registered_at:string,last_activity:string}|null */
    public function findByUserId(int $userId): ?array;

    /** @return array{id:int,secret_key:string,user_id:int,username:string,first_name:string,registered_at:string,last_activity:string}|null */
    public function findById(int $id): ?array;

    public function create(string $secretKey, int $userId, string $username, string $firstName): void;

    public function touchActivity(string $secretKey): void;

    public function delete(int $id): void;

    /**
     * @return list<array{id:int,secret_key:string,user_id:int,username:string,first_name:string,registered_at:string,last_activity:string,notifications_count:int}>
     */
    public function listAll(int $limit, int $offset): array;

    public function countAll(): int;
}
