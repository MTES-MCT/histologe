<?php

namespace App\Repository\Behaviour;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Signalement;
use Doctrine\ORM\EntityManagerInterface;

class AffectationUpdater
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function updateStatusBySignalement(AffectationStatus $status, Signalement $signalement): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->update()
            ->set('a.statut', ':status')
            ->from(Affectation::class, 'a')
            ->where('a.signalement = :signalement')
            ->setParameter('status', $status)
            ->setParameter('signalement', $signalement)
            ->getQuery()
            ->execute();
    }
}
