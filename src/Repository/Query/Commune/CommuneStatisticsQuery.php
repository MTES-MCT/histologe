<?php

namespace App\Repository\Query\Commune;

use App\Entity\Commune;
use App\Entity\Signalement;
use Doctrine\ORM\EntityManagerInterface;

class CommuneStatisticsQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function countForCommune(Commune $commune): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id)')
            ->where('s.inseeOccupant = :insee')
            ->setParameter('insee', $commune->getCodeInsee());

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
