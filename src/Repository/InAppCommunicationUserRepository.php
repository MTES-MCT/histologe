<?php

namespace App\Repository;

use App\Entity\InAppCommunication;
use App\Entity\InAppCommunicationUser;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InAppCommunicationUser>
 */
class InAppCommunicationUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InAppCommunicationUser::class);
    }

    /**
     * @throws Exception
     */
    public function markAsSeen(User $user, InAppCommunication $inAppCommunication): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            'INSERT IGNORE INTO in_app_communication_user (user_id, in_app_communication_id, seen_at)
             VALUES (:user_id, :communication_id, :seen_at)',
            [
                'user_id' => $user->getId(),
                'communication_id' => $inAppCommunication->getId(),
                'seen_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * @throws Exception
     */
    public function markAsClosed(User $user, InAppCommunication $inAppCommunication): void
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->getEntityManager()->getConnection()->executeStatement(
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
