<?php

namespace App\Repository\Behaviour;

use App\Entity\Enum\NotificationType;
use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationUpdater
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param array<int, int|string> $ids
     */
    public function markAsSeenForUser(User $user, array $ids = []): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->update(Notification::class, 'n')
            ->set('n.isSeen', 1)
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->andWhere('n.type IN (:nouveau_suivi, :nouvelle_mention, :cloture_signalement, :nouvel_abonnement, :demande_abandon_procedure)')
            ->setParameter('nouveau_suivi', NotificationType::NOUVEAU_SUIVI)
            ->setParameter('nouvelle_mention', NotificationType::NOUVELLE_MENTION)
            ->setParameter('cloture_signalement', NotificationType::CLOTURE_SIGNALEMENT)
            ->setParameter('nouvel_abonnement', NotificationType::NOUVEL_ABONNEMENT)
            ->setParameter('demande_abandon_procedure', NotificationType::DEMANDE_ABANDON_PROCEDURE)
            ->andWhere('n.deleted = :deleted')
            ->setParameter('deleted', false);

        if (\count($ids)) {
            $qb->andWhere('n.id IN (:ids)')
                ->setParameter('ids', $ids);
        }

        $qb->getQuery()->execute();
    }

    /**
     * @param array<int, Notification> $notifications
     * @param array<string, mixed>     $data
     */
    public function massUpdate(array $notifications, array $data): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->update(Notification::class, 'n')
            ->set('n.waitMailingSummary', ':waitMailingSummary')
            ->setParameter('waitMailingSummary', $data['waitMailingSummary'])
            ->where('n.id IN (:ids)')
            ->setParameter('ids', array_map(static fn (Notification $notification) => $notification->getId(), $notifications));

        if (isset($data['mailingSummarySentAt'])) {
            $qb->set('n.mailingSummarySentAt', ':mailingSummarySentAt')
                ->setParameter('mailingSummarySentAt', $data['mailingSummarySentAt']);
        }

        $qb->getQuery()->execute();
    }
}
