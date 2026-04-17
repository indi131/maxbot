<?php

declare(strict_types=1);

use App\Http\Response;

/** @var \App\Application $app */
$app = require dirname(__DIR__, 2) . '/bootstrap.php';

$app->adminAuth->startSession();
$app->adminAuth->requireLogin();

$flash = '';
$flashError = false;

if (isset($_GET['logout'])) {
    $app->adminAuth->logout();
    Response::redirect('login.php');
}

$section = isset($_GET['section']) ? (string) $_GET['section'] : 'clients';
if (! in_array($section, ['clients', 'requests', 'settings', 'broadcast'], true)) {
    $section = 'clients';
}

$scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';
$defaultBackendUrl = $host !== '' ? ($scheme . '://' . $host) : '';
$backendUrl = isset($_POST['backend_url']) ? (string) $_POST['backend_url'] : $defaultBackendUrl;
$broadcastMessage = isset($_POST['broadcast_message']) ? (string) $_POST['broadcast_message'] : '';

if (isset($_GET['download_plugin']) && (string) $_GET['download_plugin'] === '1') {
    $section = 'settings';
    try {
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('ZipArchive не поддерживается на сервере');
        }

        $pluginRoot = dirname(__DIR__, 2) . '/plugin/MaxBot';
        if (!is_dir($pluginRoot)) {
            throw new \RuntimeException('Каталог плагина не найден: ' . $pluginRoot);
        }

        $assetsDir = dirname(__DIR__) . '/assets';
        if (!is_dir($assetsDir)) {
            throw new \RuntimeException('Каталог assets не найден');
        }

        if (!is_writable($assetsDir)) {
            throw new \RuntimeException('Каталог assets недоступен для записи: ' . $assetsDir);
        }

        $zipFileName = 'plg_system_maxbot-latest.zip';
        $zipPath = $assetsDir . '/' . $zipFileName;
        if (is_file($zipPath) && !@unlink($zipPath)) {
            throw new \RuntimeException('Не удалось перезаписать архив в assets');
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Не удалось открыть ZIP для записи');
        }

        $baseName = 'MaxBot';
        $zip->addEmptyDir($baseName);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($pluginRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $fullPath = (string) $item->getPathname();
            $relative = str_replace('\\', '/', substr($fullPath, strlen($pluginRoot)));
            $relative = ltrim($relative, '/');
            $zipName = $baseName . '/' . $relative;

            if ($item->isDir()) {
                $zip->addEmptyDir($zipName);
            } else {
                $zip->addFile($fullPath, $zipName);
            }
        }

        $zip->close();

        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php'));
        $adminBase = rtrim(dirname($scriptName), '/');
        $publicBase = preg_replace('#/admin$#', '', $adminBase) ?? '';
        $downloadUrl = ($publicBase !== '' ? $publicBase : '') . '/assets/' . rawurlencode($zipFileName);

        header('Location: ' . $downloadUrl);
        exit;
    } catch (\Throwable $e) {
        $flash = 'Ошибка сборки плагина: ' . $e->getMessage();
        $flashError = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && is_string($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'set_webhook') {
        $section = 'settings';
        try {
            $ok = $app->adminService->configureWebhook($backendUrl, $app->config->require('MAX_WEBHOOK_SECRET'));
            $flash = $ok
                ? 'Webhook успешно настроен на ' . rtrim($backendUrl, '/') . '/webhook.php'
                : 'MAX API вернул отрицательный ответ при настройке webhook';
        } catch (\Throwable $e) {
            $flash = 'Ошибка настройки webhook: ' . $e->getMessage();
            $flashError = true;
        }
    } elseif ($action === 'delete_client') {
        $section = 'clients';
        $deleteId = isset($_POST['delete_id']) ? (int) $_POST['delete_id'] : 0;
        if ($deleteId > 0) {
            try {
                $app->adminService->deleteClient($deleteId);
                $flash = 'Клиент и его заявки удалены';
            } catch (\Throwable $e) {
                $flash = 'Ошибка: ' . $e->getMessage();
                $flashError = true;
            }
        }
    } elseif ($action === 'create_backup') {
        try {
            $json = $app->adminService->createBackupJson();
            $file = 'max-backup-' . date('Ymd-His') . '.json';
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $file . '"');
            echo $json;
            exit;
        } catch (\Throwable $e) {
            $section = 'settings';
            $flash = 'Ошибка создания бэкапа: ' . $e->getMessage();
            $flashError = true;
        }
    } elseif ($action === 'send_broadcast') {
        $section = 'broadcast';
        $targetClientId = isset($_POST['target_client_id']) ? (int) $_POST['target_client_id'] : 0;
        try {
            $preparedMessage = trim($broadcastMessage);
            if (isset($_FILES['broadcast_file']) && is_array($_FILES['broadcast_file']) && (int) ($_FILES['broadcast_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['broadcast_file'];
                $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_OK);
                if ($errorCode !== UPLOAD_ERR_OK) {
                    throw new \RuntimeException('Ошибка загрузки файла: код ' . $errorCode);
                }
                $tmp = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
                $origName = isset($file['name']) ? (string) $file['name'] : 'file.bin';
                if ($tmp === '' || !is_uploaded_file($tmp)) {
                    throw new \RuntimeException('Файл не получен');
                }
                if ((int) ($file['size'] ?? 0) > 10 * 1024 * 1024) {
                    throw new \RuntimeException('Файл слишком большой (максимум 10MB)');
                }

                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar'];
                if ($ext !== '' && !in_array($ext, $allowed, true)) {
                    throw new \RuntimeException('Недопустимый тип файла');
                }

                $uploadsDir = dirname(__DIR__) . '/uploads/broadcast';
                if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0775, true) && !is_dir($uploadsDir)) {
                    throw new \RuntimeException('Не удалось создать каталог для файлов');
                }

                $safeBase = preg_replace('/[^a-zA-Z0-9_-]+/', '_', pathinfo($origName, PATHINFO_FILENAME) ?: 'file');
                $safeName = $safeBase . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . ($ext !== '' ? ('.' . $ext) : '');
                $targetPath = $uploadsDir . '/' . $safeName;
                if (!move_uploaded_file($tmp, $targetPath)) {
                    throw new \RuntimeException('Не удалось сохранить файл');
                }

                $publicFileUrl = rtrim($defaultBackendUrl, '/') . '/uploads/broadcast/' . rawurlencode($safeName);
                $preparedMessage = trim($preparedMessage . "\n\n" . 'Файл: ' . $publicFileUrl);
            }

            $result = $app->adminService->sendBroadcast($preparedMessage, $targetClientId > 0 ? $targetClientId : null);
            $flash = 'Рассылка завершена: отправлено ' . $result['sent'] . ', ошибок ' . $result['failed'];
            if ($result['failed'] > 0) {
                $flashError = true;
            }
        } catch (\Throwable $e) {
            $flash = 'Ошибка рассылки: ' . $e->getMessage();
            $flashError = true;
        }
    }
}

$clientsPage = isset($_GET['clients_page']) ? (int) $_GET['clients_page'] : 1;
$requestsPage = isset($_GET['requests_page']) ? (int) $_GET['requests_page'] : 1;
$clientsPerPage = isset($_GET['clients_per_page']) ? (int) $_GET['clients_per_page'] : 20;
$requestsPerPage = isset($_GET['requests_per_page']) ? (int) $_GET['requests_per_page'] : 20;
$filterClientId = isset($_GET['filter_client_id']) ? (int) $_GET['filter_client_id'] : 0;

$clientsData = $app->adminService->getClients($clientsPage, $clientsPerPage);
$requestsData = $app->adminService->getNotifications($requestsPage, $filterClientId > 0 ? $filterClientId : null, $requestsPerPage);
$allClients = $app->users()->listAll(1000, 0);

$esc = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$sectionLink = static function (string $tab, array $extra = []) use ($section): string {
    $params = array_merge(['section' => $tab], $extra);
    return '?' . http_build_query($params);
};
$preview = static function (string $text, int $maxLen = 140): string {
    $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');
    if (mb_strlen($plain) <= $maxLen) {
        return $plain;
    }
    return rtrim(mb_substr($plain, 0, $maxLen - 1)) . '...';
};

$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php'));
$adminBase = rtrim(dirname($scriptName), '/');
$publicBase = preg_replace('#/admin$#', '', $adminBase) ?? '';
$assetHref = ($publicBase !== '' ? $publicBase : '') . '/assets/admin.css?v=4';
$buildPages = static function (int $current, int $total): array {
    if ($total <= 7) {
        return range(1, $total);
    }
    $pages = [1, max(1, $current - 1), $current, min($total, $current + 1), $total];
    $pages = array_values(array_unique($pages));
    sort($pages);
    return $pages;
};
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MAX Dashboard</title>
    <link rel="stylesheet" href="<?= $esc($assetHref) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <h1><i class="bi bi-rocket-takeoff"></i> MAX Admin</h1>
        <a class="<?= $section === 'clients' ? 'active' : '' ?>" href="<?= $esc($sectionLink('clients')) ?>"><i class="bi bi-people"></i> Клиенты</a>
        <a class="<?= $section === 'requests' ? 'active' : '' ?>" href="<?= $esc($sectionLink('requests')) ?>"><i class="bi bi-card-checklist"></i> Заявки</a>
        <a class="<?= $section === 'broadcast' ? 'active' : '' ?>" href="<?= $esc($sectionLink('broadcast')) ?>"><i class="bi bi-megaphone"></i> Рассылка</a>
        <a class="<?= $section === 'settings' ? 'active' : '' ?>" href="<?= $esc($sectionLink('settings')) ?>"><i class="bi bi-sliders2"></i> Настройки</a>
        <div class="sidebar-bottom">
            <a class="sidebar-download-btn" href="<?= $esc($sectionLink('settings', ['download_plugin' => 1])) ?>"><i class="bi bi-download"></i> Скачать плагин</a>
            <a href="change-password.php"><i class="bi bi-shield-lock"></i> Сменить пароль</a>
            <a href="?logout=1"><i class="bi bi-box-arrow-right"></i> Выход</a>
        </div>
    </aside>

    <main class="content">
        <section class="stats-row">
            <div class="stat">
                <span>Клиенты</span>
                <strong><?= (int) $clientsData['total'] ?></strong>
            </div>
            <div class="stat">
                <span>Заявки</span>
                <strong><?= (int) $requestsData['total'] ?></strong>
            </div>
        </section>

        <?php if ($flash !== ''): ?>
            <p class="flash <?= $flashError ? 'flash-error' : '' ?>"><?= $esc($flash) ?></p>
        <?php endif; ?>

        <?php if ($section === 'clients'): ?>
            <section class="panel">
                <h2>Клиенты</h2>
                <form method="get" class="filters">
                    <input type="hidden" name="section" value="clients">
                    <label>
                        На странице
                        <select name="clients_per_page">
                            <?php foreach ([10, 20, 50, 100] as $n): ?>
                                <option value="<?= $n ?>" <?= $clientsPerPage === $n ? 'selected' : '' ?>><?= $n ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="submit">Применить</button>
                </form>
                <div class="table-wrap">
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
                        <?php foreach ($clientsData['items'] as $row): ?>
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
                                    <div class="actions-cell">
                                        <a class="btn-light" href="<?= $esc($sectionLink('requests', ['filter_client_id' => (int) $row['id']])) ?>">Заявки</a>
                                        <form method="post" onsubmit="return confirm('Удалить клиента и все его заявки?');">
                                            <input type="hidden" name="action" value="delete_client">
                                            <input type="hidden" name="delete_id" value="<?= (int) $row['id'] ?>">
                                            <button type="submit" class="btn-danger">Удалить</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <footer class="pager">
                    <span>Страница <?= (int) $clientsData['page'] ?> из <?= (int) $clientsData['pages'] ?> (всего <?= (int) $clientsData['total'] ?>)</span>
                    <?php if ($clientsData['page'] > 1): ?>
                        <a href="<?= $esc($sectionLink('clients', ['clients_page' => (int) $clientsData['page'] - 1, 'clients_per_page' => $clientsPerPage])) ?>">Назад</a>
                    <?php endif; ?>
                    <?php foreach ($buildPages((int) $clientsData['page'], (int) $clientsData['pages']) as $p): ?>
                        <a class="<?= $p === (int) $clientsData['page'] ? 'current' : '' ?>" href="<?= $esc($sectionLink('clients', ['clients_page' => $p, 'clients_per_page' => $clientsPerPage])) ?>"><?= $p ?></a>
                    <?php endforeach; ?>
                    <?php if ($clientsData['page'] < $clientsData['pages']): ?>
                        <a href="<?= $esc($sectionLink('clients', ['clients_page' => (int) $clientsData['page'] + 1, 'clients_per_page' => $clientsPerPage])) ?>">Вперёд</a>
                    <?php endif; ?>
                </footer>
            </section>
        <?php elseif ($section === 'requests'): ?>
            <section class="panel">
                <h2>Заявки</h2>
                <form method="get" class="filters">
                    <input type="hidden" name="section" value="requests">
                    <label>
                        На странице
                        <select name="requests_per_page">
                            <?php foreach ([10, 20, 50, 100] as $n): ?>
                                <option value="<?= $n ?>" <?= $requestsPerPage === $n ? 'selected' : '' ?>><?= $n ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        Клиент
                        <select name="filter_client_id">
                            <option value="0">Все клиенты</option>
                            <?php foreach ($allClients as $client): ?>
                                <option value="<?= (int) $client['id'] ?>" <?= $filterClientId === (int) $client['id'] ? 'selected' : '' ?>>
                                    #<?= (int) $client['id'] ?> <?= $esc((string) $client['first_name']) ?> (<?= (int) $client['user_id'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button type="submit">Фильтровать</button>
                </form>

                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Клиент</th>
                            <th>MAX user_id</th>
                            <th>Создана</th>
                            <th>Содержание</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($requestsData['items'] as $row): ?>
                            <tr>
                                <td><?= (int) $row['id'] ?></td>
                                <td><?= $esc((string) $row['first_name']) ?> <?= $esc((string) $row['username']) ?></td>
                                <td><?= (int) $row['user_id'] ?></td>
                                <td><?= $esc((string) $row['created_at']) ?></td>
                                <td><?= $esc($preview((string) $row['content'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <footer class="pager">
                    <span>Страница <?= (int) $requestsData['page'] ?> из <?= (int) $requestsData['pages'] ?> (всего <?= (int) $requestsData['total'] ?>)</span>
                    <?php if ($requestsData['page'] > 1): ?>
                        <a href="<?= $esc($sectionLink('requests', ['requests_page' => (int) $requestsData['page'] - 1, 'filter_client_id' => $filterClientId, 'requests_per_page' => $requestsPerPage])) ?>">Назад</a>
                    <?php endif; ?>
                    <?php foreach ($buildPages((int) $requestsData['page'], (int) $requestsData['pages']) as $p): ?>
                        <a class="<?= $p === (int) $requestsData['page'] ? 'current' : '' ?>" href="<?= $esc($sectionLink('requests', ['requests_page' => $p, 'filter_client_id' => $filterClientId, 'requests_per_page' => $requestsPerPage])) ?>"><?= $p ?></a>
                    <?php endforeach; ?>
                    <?php if ($requestsData['page'] < $requestsData['pages']): ?>
                        <a href="<?= $esc($sectionLink('requests', ['requests_page' => (int) $requestsData['page'] + 1, 'filter_client_id' => $filterClientId, 'requests_per_page' => $requestsPerPage])) ?>">Вперёд</a>
                    <?php endif; ?>
                </footer>
            </section>
        <?php elseif ($section === 'broadcast'): ?>
            <section class="panel">
                <h2>Рассылка</h2>
                <div class="settings-grid">
                    <div class="card">
                        <h3>Отправить сообщение</h3>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="send_broadcast">
                            <label>
                                Кому отправлять
                                <select name="target_client_id">
                                    <option value="0">Всем клиентам</option>
                                    <?php foreach ($allClients as $client): ?>
                                        <option value="<?= (int) $client['id'] ?>">
                                            #<?= (int) $client['id'] ?> <?= $esc((string) $client['first_name']) ?> (<?= (int) $client['user_id'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                Текст сообщения
                                <textarea name="broadcast_message" rows="8" required><?= $esc($broadcastMessage) ?></textarea>
                            </label>
                            <label>
                                Прикрепить файл (необязательно, до 10MB)
                                <input type="file" name="broadcast_file">
                            </label>
                            <button type="submit">Запустить рассылку</button>
                        </form>
                    </div>
                    <div class="card">
                        <h3>Подсказка</h3>
                        <p>Для форматирования можно использовать HTML-теги:</p>
                        <p><code>&lt;b&gt;</code>, <code>&lt;strong&gt;</code> — жирный<br>
                           <code>&lt;i&gt;</code>, <code>&lt;em&gt;</code> — курсив<br>
                           <code>&lt;u&gt;</code> — подчёркнутый<br>
                           <code>&lt;s&gt;</code>, <code>&lt;del&gt;</code> — зачёркнутый<br>
                           <code>&lt;code&gt;</code>, <code>&lt;pre&gt;</code> — моноширинный<br>
                           <code>&lt;a href="https://..."&gt;ссылка&lt;/a&gt;</code> — ссылка</p>
                        <p>Переносы строк:</p>
                        <p><code>&lt;br&gt;</code> — одна новая строка,<br><code>&lt;br&gt;&lt;br&gt;</code> — пустая строка между абзацами.</p>
                        <p>Рекомендуется сначала отправить сообщение одному клиенту, затем — всем.</p>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <section class="panel">
                <h2>Настройки</h2>
                <div class="settings-grid">
                    <div class="card">
                        <h3>Webhook бота</h3>
                        <form method="post">
                            <input type="hidden" name="action" value="set_webhook">
                            <label>
                                URL бэкенда
                                <input type="text" name="backend_url" value="<?= $esc($backendUrl) ?>" placeholder="https://max.unicode24bot.ru" required>
                            </label>
                            <button type="submit">Сохранить в боте</button>
                        </form>
                        <p>Будет установлен webhook: <code><?= $esc(rtrim($backendUrl, '/')) ?>/webhook.php</code></p>
                    </div>
                    <div class="card">
                        <h3>Бэкап</h3>
                        <form method="post">
                            <input type="hidden" name="action" value="create_backup">
                            <button type="submit">Скачать бэкап (JSON)</button>
                        </form>
                        <p>В бэкап входят клиенты и заявки.</p>
                    </div>
                    <div class="card">
                        <h3>Отладочная информация</h3>
                        <ul class="debug-list">
                            <li>PHP: <?= $esc(PHP_VERSION) ?></li>
                            <li>ZipArchive: <?= class_exists('ZipArchive') ? 'установлен' : 'не установлен' ?></li>
                            <li>Клиентов: <?= (int) $clientsData['total'] ?></li>
                            <li>Заявок: <?= (int) $requestsData['total'] ?></li>
                            <li>API: <?= $esc($app->config->get('MAX_API_BASE_URL')) ?></li>
                            <li>WEBAPP_URL: <?= $esc($app->config->get('WEBAPP_URL')) ?></li>
                        </ul>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>
</body>
</html>