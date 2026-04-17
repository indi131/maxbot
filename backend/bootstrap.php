<?php

declare(strict_types=1);

use App\Application;
use App\Auth\AdminAuth;
use App\Config;
use App\Database\Connection;
use App\Messenger\MaxBot;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use App\Service\AdminService;
use App\Service\BotService;
use App\Service\NotificationService;

require_once __DIR__ . '/vendor/autoload.php';

$config = new Config(__DIR__);
$pdo = Connection::getInstance($config);

$userRepo = new UserRepository($pdo);
$notifRepo = new NotificationRepository($pdo);
$messenger = new MaxBot(
    $config->require('MAX_BOT_TOKEN'),
    $config->require('WEBAPP_URL'),
    $config->get('MAX_API_BASE_URL')
);

$notificationService = new NotificationService($userRepo, $notifRepo, $messenger, $config);
$botService = new BotService($userRepo, $messenger);
$adminService = new AdminService($userRepo, $notifRepo, $messenger);
$adminAuth = new AdminAuth($pdo);

return new Application(
    $config,
    $userRepo,
    $notifRepo,
    $notificationService,
    $botService,
    $adminService,
    $adminAuth,
);
