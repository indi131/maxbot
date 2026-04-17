<?php

declare(strict_types=1);

use App\Http\Response;

/** @var \App\Application $app */
$app = require dirname(__DIR__, 2) . '/bootstrap.php';

$app->adminAuth->startSession();
$app->adminAuth->requireLogin();

if (isset($_GET['logout'])) {
    $app->adminAuth->logout();
    Response::redirect('login.php');
}

$error = '';
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = isset($_POST['current_password']) ? (string) $_POST['current_password'] : '';
    $new = isset($_POST['new_password']) ? (string) $_POST['new_password'] : '';
    $confirm = isset($_POST['confirm_password']) ? (string) $_POST['confirm_password'] : '';

    if ($new !== $confirm) {
        $error = 'Новый пароль и подтверждение не совпадают';
    } else {
        $id = $app->adminAuth->adminId();
        if ($id === null) {
            Response::redirect('login.php');
        }

        try {
            $app->adminAuth->changePassword($id, $current, $new);
            $ok = 'Пароль изменён';
        } catch (\InvalidArgumentException $e) {
            $error = $e->getMessage();
        } catch (\Throwable $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    }
}

$esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Смена пароля</title>
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>
<header class="top">
    <h1>Смена пароля</h1>
    <nav>
        <a href="index.php">Клиенты</a>
        <a href="?logout=1">Выход</a>
    </nav>
</header>

<div class="login-box" style="margin:1.5rem auto; max-width:400px;">
    <?php if ($ok !== ''): ?>
        <p class="flash" style="margin:0 0 1rem;"><?= $esc($ok) ?></p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
        <p class="error" style="margin:0 0 1rem;"><?= $esc($error) ?></p>
    <?php endif; ?>
    <form method="post" action="">
        <label>Текущий пароль
            <input type="password" name="current_password" required autocomplete="current-password">
        </label>
        <label>Новый пароль (мин. 8 символов)
            <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
        </label>
        <label>Подтверждение
            <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password">
        </label>
        <button type="submit">Сохранить</button>
    </form>
</div>
</body>
</html>
