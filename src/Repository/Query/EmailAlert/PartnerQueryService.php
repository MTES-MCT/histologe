<?php

namespace App\Repository\Query\EmailAlert;

use App\Entity\EmailDeliveryIssue;
use App\Entity\Enum\UserStatus;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\UserPartner;
use App\Entity\UserSignalementSubscription;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

class PartnerQueryService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countPartnerWithEmailIssue(?string $email = null): int
    {
        if (null === $email) {
            return 0;
        }
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(p.id)')
            ->from(Partner::class, 'p')
            ->innerJoin(
                EmailDeliveryIssue::class,
                'edi',
                'WITH',
                'edi.email = p.email'
            )
            ->where('p.email = :email')
            ->setParameter('email', $email);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countSubscribers(Signalement $signalement, Partner $partner, bool $withEmailIssue = false): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(uss.id)')
            ->from(UserSignalementSubscription::class, 'uss')
            ->innerJoin('uss.user', 'u')
            ->innerJoin(UserPartner::class, 'up', 'WITH', 'up.user = u')
            ->where('uss.signalement = :sid')
            ->andWhere('up.partner = :pid')
            ->andWhere('u.statut = :statut')
            ->setParameter('statut', UserStatus::ACTIVE)
            ->setParameter('sid', $signalement->getId())
            ->setParameter('pid', $partner->getId());

        if ($withEmailIssue) {
            $qb->innerJoin(EmailDeliveryIssue::class, 'edi', 'WITH', 'edi.email = u.email');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function shouldDisplayAlertEmailIssue(Signalement $signalement, Partner $partner): bool
    {
        $total = $this->countSubscribers($signalement, $partner);
        if (0 === $total) {
            return false;
        }

        $totalWithIssue = $this->countSubscribers($signalement, $partner, true);

        if ($totalWithIssue !== $total) {
            return false;
        }

        $countPartnerWithEmailIssue = $this->countPartnerWithEmailIssue($partner->getEmail());

        return $countPartnerWithEmailIssue > 0 || null === $partner->getEmail();
    }
}
