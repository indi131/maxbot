<?php

declare(strict_types=1);

use App\Http\Response;

/** @var \App\Application $app */
$app = require dirname(__DIR__, 2) . '/bootstrap.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$userIdBack = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$startParam = isset($_GET['WebAppStartParam']) ? trim((string) $_GET['WebAppStartParam']) : '';
if ($id <= 0 && $startParam !== '' && preg_match('/^d(\d+)_u(\d+)$/', $startParam, $m)) {
    $id = (int) $m[1];
    $userIdBack = (int) $m[2];
}
if ($id <= 0) {
    Response::html('<p>Некорректный id</p>', 400);
}

$row = $app->notifications()->findById($id);
if ($row === null) {
    Response::html('<p>Запись не найдена</p>', 404);
}

$esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$bodyHtml = strip_tags(
    (string) $row['content'],
    '<br><br/><p><strong><b><em><i><ul><ol><li><span><div><small><u><h2><h3><h4>'
);

ob_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Заявка #<?= (int) $row['id'] ?></title>
    <link rel="stylesheet" href="../assets/app.css">
    <script src="https://dev.max.ru/max-web-app.js"></script>
</head>
<body>
<div class="header"><h1>Заявка #<?= (int) $row['id'] ?></h1></div>
<div class="wrapper">
    <div class="item single">
        <div class="date"><?= $esc((string) $row['created_at']) ?></div>
        <div class="content"><?= $bodyHtml ?></div>
    </div>
    <?php /*
    <p class="back"><?php if ($userIdBack > 0): ?><a href="index.php?user_id=<?= $userIdBack ?>">К списку</a><?php else: ?><a href="#" onclick="history.back();return false;">Назад</a><?php endif; ?></p>
    */ ?>
</div>
</body>
</html>
<?php
Response::html((string) ob_get_clean());
