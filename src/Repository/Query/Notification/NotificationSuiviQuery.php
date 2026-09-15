<?php

namespace App\Repository\Query\Notification;

use App\Dto\NotificationSuiviUser;
use App\Entity\Signalement;
use App\Repository\NotificationRepository;

class NotificationSuiviQuery
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
    ) {
    }

    /**
     * @return NotificationSuiviUser[]
     */
    public function getNotificationsFrom(Signalement $signalement): array
    {
        return array_map(
            static fn (array $row) => new NotificationSuiviUser(
                (int) $row['suiviId'],
                (int) $row['userId'],
                (bool) $row['isSeen'],
                $row['suiviCreatedAt']
            ),
            $this->notificationRepository->createQueryBuilder('n')
                ->select('s.id as suiviId', 'u.id as userId', 'n.isSeen as isSeen', 's.createdAt as suiviCreatedAt')
                ->join('n.user', 'u')
                ->join('n.suivi', 's')
                ->andWhere('n.signalement = :signalement')
                ->andWhere('u.id IN (:usager_ids)')
                ->setParameter('signalement', $signalement)
                ->setParameter('usager_ids', $signalement->getUsagerIds())
                ->orderBy('s.createdAt', 'DESC')
                ->getQuery()
                ->getArrayResult()
        );
    }
}
