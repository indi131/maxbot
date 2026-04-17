<?php

declare(strict_types=1);

use App\Http\Response;

/** @var \App\Application $app */
$app = require dirname(__DIR__, 2) . '/bootstrap.php';

$app->adminAuth->startSession();
$app->adminAuth->requireLogin();

$flash = '';

if (isset($_GET['logout'])) {
    $app->adminAuth->logout();
    Response::redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];
    if ($deleteId > 0) {
        try {
            $app->adminService->deleteClient($deleteId);
            $flash = 'Клиент удалён';
        } catch (\Throwable $e) {
            $flash = 'Ошибка: ' . $e->getMessage();
        }
    }
}

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$data = $app->adminService->getClients($page);

$esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Клиенты</title>
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>
<header class="top">
    <h1>Активные клиенты</h1>
    <nav>
        <a href="change-password.php">Пароль</a>
        <a href="?logout=1">Выход</a>
    </nav>
</header>

<?php if ($flash !== ''): ?>
    <p class="flash"><?= $esc($flash) ?></p>
<?php endif; ?>

<main class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Ключ</th>
            <th>MAX user_id</th>
            <th>Имя</th>
            <th>Ник</th>
            <th>Заявок</th>
            <th>Регистрация</th>
            <th>Активность</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($data['items'] as $row): ?>
            <tr>
                <td><?= (int) $row['id'] ?></td>
                <td><code><?= $esc((string) $row['secret_key']) ?></code></td>
                <td><?= (int) $row['user_id'] ?></td>
                <td><?= $esc((string) $row['first_name']) ?></td>
                <td><?= $esc((string) $row['username']) ?></td>
                <td><?= (int) $row['notifications_count'] ?></td>
                <td><?= $esc((string) $row['registered_at']) ?></td>
                <td><?= $esc((string) $row['last_activity']) ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('Удалить клиента?');">
                        <input type="hidden" name="delete_id" value="<?= (int) $row['id'] ?>">
                        <button type="submit" class="btn-danger">Удалить</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>

<footer class="pager">
    <span>Страница <?= (int) $data['page'] ?> из <?= (int) $data['pages'] ?> (всего <?= (int) $data['total'] ?>)</span>
    <?php if ($data['page'] > 1): ?>
        <a href="?page=<?= (int) $data['page'] - 1 ?>">Назад</a>
    <?php endif; ?>
    <?php if ($data['page'] < $data['pages']): ?>
        <a href="?page=<?= (int) $data['page'] + 1 ?>">Вперёд</a>
    <?php endif; ?>
</footer>
</body>
</html>
