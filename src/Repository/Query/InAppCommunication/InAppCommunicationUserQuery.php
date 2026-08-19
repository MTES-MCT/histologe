<?php

namespace App\Repository\Query\InAppCommunication;

use App\Entity\InAppCommunication;
use App\Entity\User;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;

class InAppCommunicationUserQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * @throws Exception
     */
    public function markAsSeen(User $user, InAppCommunication $inAppCommunication): void
    {
        $this->entityManager->getConnection()->executeStatement(
            'INSERT IGNORE INTO in_app_communication_user (user_id, in_app_communication_id, seen_at)
             VALUES (:user_id, :communication_id, :seen_at)',
            [
                'user_id' => $user->getId(),
                'communication_id' => $inAppCommunication->getId(),
                'seen_at' => $this->clock->now()->format('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * @throws Exception
     */
    public function markAsClosed(User $user, InAppCommunication $inAppCommunication): void
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');
        $this->entityManager->getConnection()->executeStatement(
            'INSERT INTO in_app_communication_user (user_id, in_app_communication_id, seen_at, closed_at)
             VALUES (:user_id, :communication_id, :seen_at, :closed_at)
             ON DUPLICATE KEY UPDATE closed_at = :closed_at',
            [
                'user_id' => $user->getId(),
                'communication_id' => $inAppCommunication->getId(),
                'seen_at' => $now,
                'closed_at' => $now,
            ]
        );
    }
}
