<?php

declare(strict_types=1);

use App\Http\Response;

/** @var \App\Application $app */
$app = require dirname(__DIR__, 2) . '/bootstrap.php';

$app->adminAuth->startSession();

$error = '';

if ($app->adminAuth->isLoggedIn()) {
    Response::redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = isset($_POST['login']) ? (string) $_POST['login'] : '';
    $password = isset($_POST['password']) ? (string) $_POST['password'] : '';
    if ($app->adminAuth->attempt($login, $password)) {
        Response::redirect('index.php');
    }
    $error = 'Неверный логин или пароль';
}

$esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Вход администратора</title>
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body class="login-page">
<div class="login-box">
    <h1>Админ-панель</h1>
    <?php if ($error !== ''): ?>
        <p class="error"><?= $esc($error) ?></p>
    <?php endif; ?>
    <form method="post" action="">
        <label>Логин <input type="text" name="login" required autocomplete="username"></label>
        <label>Пароль <input type="password" name="password" required autocomplete="current-password"></label>
        <button type="submit">Войти</button>
    </form>
</div>
</body>
</html>
