<?php

namespace App\Repository\Query\User;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Suivi;
use App\Entity\User;
use App\Entity\UserSignalementSubscription;
use Doctrine\ORM\EntityManagerInterface;

class UserSubscriptionQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function countSubscriptionsOnActiveSignalementsForUser(User $user): int
    {
        $qb = $this->entityManager->createQueryBuilder()->from(UserSignalementSubscription::class, 'sub')
            ->select('COUNT(sub.id)')
            ->innerJoin('sub.signalement', 's')
            ->innerJoin('s.address', 'address')
            ->where('sub.user = :user')
            ->setParameter('user', $user)
            ->andWhere('s.statut = :statut')
            ->setParameter('statut', SignalementStatus::ACTIVE);

        if ($user->isTerritoryAdmin()) {
            $qb->andWhere('address.territory IN (:territories)')
                ->setParameter('territories', $user->getPartnersTerritories());
        } else {
            $qb->innerJoin('s.affectations', 'a')
                ->andWhere('a.statut = :affectationStatut')
                ->andWhere('a.partner IN (:partners)')
                ->setParameter('affectationStatut', AffectationStatus::ACCEPTED)
                ->setParameter('partners', $user->getPartners());
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<UserSignalementSubscription>|int
     */
    public function getSubscriptionsOnSignalementsWithoutInteractionsForUser(User $user, ?bool $count = false): array|int
    {
        $qb = $this->entityManager->createQueryBuilder()->from(UserSignalementSubscription::class, 'sub');
        if ($count) {
            $qb->select('COUNT(sub)');
        } else {
            $qb->select('sub');
        }
        $qb->innerJoin('sub.signalement', 's')
            ->leftJoin(Suivi::class, 'suivi', 'WITH', 'suivi.signalement = s AND suivi.createdBy = :user')
            ->leftJoin('s.affectations', 'affectation', 'WITH', 'affectation.answeredBy = :user')
            ->where('sub.user = :user')
            ->setParameter('user', $user)
            ->andWhere('suivi.id IS NULL')
            ->andWhere('affectation.id IS NULL');

        if ($count) {
            return (int) $qb->getQuery()->getSingleScalarResult();
        }

        return $qb->getQuery()->getResult();
    }
}
