<?php

namespace App\Repository\Query\EmailAlert;

use App\Entity\EmailDeliveryIssue;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\UserPartner;
use App\Entity\UserSignalementSubscription;
use Doctrine\ORM\EntityManagerInterface;

class PartnerQueryService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function countSubscribers(Signalement $signalement, Partner $partner, bool $withEmailIssue = false): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(uss.id)')
            ->from(UserSignalementSubscription::class, 'uss')
            ->innerJoin('uss.user', 'u')
            ->innerJoin(UserPartner::class, 'up', 'WITH', 'up.user = u')
            ->where('uss.signalement = :sid')
            ->andWhere('up.partner = :pid')
            ->setParameter('sid', $signalement->getId())
            ->setParameter('pid', $partner->getId());

        if ($withEmailIssue) {
            $qb->innerJoin(EmailDeliveryIssue::class, 'edi', 'WITH', 'edi.email = u.email');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

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

        return $partner->hasEmailIssue() || null === $partner->getEmail();
    }
}
