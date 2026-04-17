<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MAX Bot Backend</title>
    <link rel="stylesheet" href="assets/app.css">
    <style>
        .box { max-width: 640px; margin: 2rem auto; padding: 0 1rem; }
        .links { display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem; }
        .links a { color: var(--accent); text-decoration: none; }
        .links a:hover { text-decoration: underline; }
        .warn { margin-top: 1.5rem; padding: 1rem; border: 1px solid var(--border); border-radius: 8px; color: var(--muted); font-size: 0.9rem; }
        code { font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="header"><h1>MAX Bot Backend</h1></div>
<div class="box">
    <p>Сервис приёма заявок и уведомлений в MAX. Ниже — прямые ссылки на скрипты (относительно корня сайта).</p>
    <div class="links">
        <a href="admin/login.php">Админ-панель (вход)</a>
        <a href="notify.php">Приём заявки (POST JSON)</a>
        <a href="webhook.php">Webhook для MAX</a>
        <a href="webapp/index.php">Webapp (нужен <code>?user_id=…</code>)</a>
    </div>
    <div class="warn">
        <strong>Важно для Apache:</strong> корень сайта (DocumentRoot) должен указывать на каталог <code>public</code> внутри проекта,
        а не на родительскую папку. Иначе пути вида <code>/admin</code> не найдут файлы — используйте
        <code>/admin/</code> или <code>/admin/login.php</code>. В корне лежит этот <code>index.php</code>, чтобы не было ошибки 403 на <code>/</code>.
    </div>
</div>
</body>
</html>
