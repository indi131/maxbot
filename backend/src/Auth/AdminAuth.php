<?php

declare(strict_types=1);

namespace App\Auth;

use PDO;

final class AdminAuth
{
    private const SESSION_KEY = 'max_backend_admin_id';

    /** @var PDO */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function isLoggedIn(): bool
    {
        $this->startSession();

        return isset($_SESSION[self::SESSION_KEY]) && is_int($_SESSION[self::SESSION_KEY]);
    }

    public function requireLogin(): void
    {
        if (! $this->isLoggedIn()) {
            \App\Http\Response::redirect('login.php');
        }
    }

    public function attempt(string $login, string $password): bool
    {
        $login = trim($login);
        if ($login === '' || $password === '') {
            return false;
        }

        $stmt = $this->pdo->prepare('SELECT id, password_hash FROM admins WHERE login = ? LIMIT 1');
        $stmt->execute([$login]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false || ! is_string($row['password_hash'] ?? null)) {
            return false;
        }

        if (! password_verify($password, $row['password_hash'])) {
            return false;
        }

        $this->startSession();
        $_SESSION[self::SESSION_KEY] = (int) $row['id'];

        return true;
    }

    public function logout(): void
    {
        $this->startSession();
        unset($_SESSION[self::SESSION_KEY]);
    }

    public function adminId(): ?int
    {
        $this->startSession();
        if (! isset($_SESSION[self::SESSION_KEY]) || ! is_int($_SESSION[self::SESSION_KEY])) {
            return null;
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public function changePassword(int $adminId, string $currentPassword, string $newPassword): void
    {
        $newPassword = trim($newPassword);
        if (strlen($newPassword) < 8) {
            throw new \InvalidArgumentException('Новый пароль не короче 8 символов');
        }

        $stmt = $this->pdo->prepare('SELECT password_hash FROM admins WHERE id = ? LIMIT 1');
        $stmt->execute([$adminId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false || ! is_string($row['password_hash'] ?? null)) {
            throw new \RuntimeException('Администратор не найден');
        }

        if (! password_verify($currentPassword, $row['password_hash'])) {
            throw new \InvalidArgumentException('Неверный текущий пароль');
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $upd = $this->pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
        $upd->execute([$hash, $adminId]);
    }
}
