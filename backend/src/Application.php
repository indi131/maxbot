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
    public function __construct(
        public readonly Config $config,
        private readonly UserRepositoryInterface $users,
        private readonly NotificationRepositoryInterface $notifications,
        public readonly NotificationService $notificationService,
        public readonly BotService $botService,
        public readonly AdminService $adminService,
        public readonly AdminAuth $adminAuth,
    ) {
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
