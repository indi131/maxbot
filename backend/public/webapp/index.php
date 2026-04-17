<?php

declare(strict_types=1);

use App\Http\Response;

/** @var \App\Application $app */
$app = require dirname(__DIR__, 2) . '/bootstrap.php';

$startParam = isset($_GET['WebAppStartParam']) ? trim((string) $_GET['WebAppStartParam']) : '';
if ($startParam === '' && isset($_GET['startapp'])) {
    $startParam = trim((string) $_GET['startapp']);
}
if ($startParam !== '' && preg_match('/^d(\d+)_u(\d+)$/', $startParam, $m)) {
    header('Location: detail.php?id=' . (int) $m[1] . '&user_id=' . (int) $m[2], true, 302);
    exit;
}

$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$rows = $userId > 0 ? $app->notifications()->findByUserId($userId, 20) : [];

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
    <script src="https://dev.max.ru/max-web-app.js"></script>
</head>
<body>
<div class="header"><h1>Последние заявки</h1></div>
<div class="wrapper">
<?php if ($userId <= 0): ?>
    <p class="empty" id="loading-text">Загрузка профиля в MAX...</p>
    <script>
        (function () {
            try {
                var user = window.WebApp && window.WebApp.initDataUnsafe && window.WebApp.initDataUnsafe.user;
                if (user && user.id) {
                    window.location.replace('index.php?user_id=' + encodeURIComponent(String(user.id)));
                    return;
                }
            } catch (e) {}
            document.getElementById('loading-text').textContent = 'Не удалось получить user_id из MAX.';
        })();
    </script>
<?php elseif ($rows === []): ?>
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
