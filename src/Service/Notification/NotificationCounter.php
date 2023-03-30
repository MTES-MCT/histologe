<?php

namespace App\Service\Notification;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Twig\Extension\RuntimeExtensionInterface;

class NotificationCounter implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository
    ) {
    }

    public function countUnseenNotification(User $user): int
    {
        return $this->notificationRepository->count(['user' => $user, 'isSeen' => 0, 'type' => Notification::TYPE_SUIVI]);
    }
}
