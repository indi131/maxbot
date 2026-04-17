<?php

declare(strict_types=1);

use App\Http\Response;

/** @var \App\Application $app */
$app = require dirname(__DIR__, 2) . '/bootstrap.php';

$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
if ($userId <= 0) {
    Response::html('<p>Некорректный user_id</p>', 400);
}

$rows = $app->notifications()->findByUserId($userId, 20);

$esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

ob_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Заявки</title>
    <link rel="stylesheet" href="../assets/app.css">
</head>
<body>
<div class="header"><h1>Последние заявки</h1></div>
<div class="wrapper">
<?php if ($rows === []): ?>
    <p class="empty">Нет записей</p>
<?php else: ?>
    <?php foreach ($rows as $row): ?>
        <article class="item">
            <div class="meta">
                <a href="detail.php?id=<?= (int) $row['id'] ?>&amp;user_id=<?= $userId ?>">#<?= (int) $row['id'] ?></a>
                <span class="date"><?= $esc((string) $row['created_at']) ?></span>
            </div>
            <div class="excerpt"><?= $esc(mb_substr((string) $row['content'], 0, 200)) ?><?= mb_strlen((string) $row['content']) > 200 ? '…' : '' ?></div>
        </article>
    <?php endforeach; ?>
<?php endif; ?>
</div>
</body>
</html>
<?php
Response::html((string) ob_get_clean());
