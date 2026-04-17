<?php

declare(strict_types=1);

namespace App;

use App\Auth\AdminAuth;
use App\Contracts\NotificationRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Service\AdminService;
use App\Service\BotService;
use App\Service\NotificationService;

final class Application
{
    /** @var Config */
    public $config;

    /** @var UserRepositoryInterface */
    private $users;

    /** @var NotificationRepositoryInterface */
    private $notifications;

    /** @var NotificationService */
    public $notificationService;

    /** @var BotService */
    public $botService;

    /** @var AdminService */
    public $adminService;

    /** @var AdminAuth */
    public $adminAuth;

    public function __construct(
        Config $config,
        UserRepositoryInterface $users,
        NotificationRepositoryInterface $notifications,
        NotificationService $notificationService,
        BotService $botService,
        AdminService $adminService,
        AdminAuth $adminAuth
    ) {
        $this->config = $config;
        $this->users = $users;
        $this->notifications = $notifications;
        $this->notificationService = $notificationService;
        $this->botService = $botService;
        $this->adminService = $adminService;
        $this->adminAuth = $adminAuth;
    }

    public function users(): UserRepositoryInterface
    {
        return $this->users;
    }

    public function notifications(): NotificationRepositoryInterface
    {
        return $this->notifications;
    }
}
