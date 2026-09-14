<?php

namespace App\Repository\Behaviour;

use App\Entity\Enum\NotificationType;
use App\Entity\Notification;
use App\Entity\Signalement;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationDeleter
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param array<int, int|string> $ids
     */
    public function deleteForUser(User $user, array $ids = []): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->update(Notification::class, 'n')
            ->set('n.deleted', 1)
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->andWhere('n.deleted = :deleted')
            ->setParameter('deleted', false)
            ->andWhere('n.type IN (:nouveau_suivi, :nouvelle_mention, :cloture_signalement, :nouvel_abonnement, :demande_abandon_procedure)')
            ->setParameter('nouveau_suivi', NotificationType::NOUVEAU_SUIVI)
            ->setParameter('nouvelle_mention', NotificationType::NOUVELLE_MENTION)
            ->setParameter('cloture_signalement', NotificationType::CLOTURE_SIGNALEMENT)
            ->setParameter('nouvel_abonnement', NotificationType::NOUVEL_ABONNEMENT)
            ->setParameter('demande_abandon_procedure', NotificationType::DEMANDE_ABANDON_PROCEDURE);

        if (\count($ids)) {
            $qb->andWhere('n.id IN (:ids)')
                ->setParameter('ids', $ids);
        }

        $qb->getQuery()->execute();
    }

    public function deleteBySignalement(Signalement $signalement): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete(Notification::class, 'n')
            ->where('n.signalement = :signalement')
            ->setParameter('signalement', $signalement);

        $qb->getQuery()->execute();
    }
}
