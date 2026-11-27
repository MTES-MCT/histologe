<?php

namespace App\Repository\Query\Signalement;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Territory;
use Doctrine\ORM\EntityManagerInterface;

class SignalementMassAffectationQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Compte, parmi les ids donnés, les signalements en cours sur le territoire.
     * Permet de vérifier qu'un lot reçu du front est toujours éligible à l'affectation en masse.
     *
     * @param array<int> $signalementIds
     */
    public function countActiveInTerritory(array $signalementIds, Territory $territory): int
    {
        if (empty($signalementIds)) {
            return 0;
        }

        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(s.id)')
            ->from(Signalement::class, 's')
            ->where('s.id IN (:ids)')
            ->andWhere('s.territory = :territory')
            ->andWhere('s.statut = :statut')
            ->setParameter('ids', $signalementIds)
            ->setParameter('territory', $territory)
            ->setParameter('statut', SignalementStatus::ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
