<?php

namespace App\Repository\Query\Statistics;

use App\Dto\StatisticsFilters;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Territory;
use Doctrine\ORM\EntityManagerInterface;

class MotifClotureStatisticsQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FilteredStatisticsQuery $filteredStatisticsQuery,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByMotifCloture(?Territory $territory, ?int $year, bool $removeImported = false): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id) AS count, s.motifCloture')
            ->where('s.motifCloture IS NOT NULL')
            ->andWhere('s.motifCloture != \'0\'')
            ->andWhere('s.closedAt IS NOT NULL')
            ->andWhere('s.statut = :statut')
            ->setParameter('statut', SignalementStatus::CLOSED);

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }

        if ($year) {
            $qb->andWhere('YEAR(s.createdAt) = :year')->setParameter('year', $year);
        }

        $qb->groupBy('s.motifCloture');
        $qb->orderBy('s.motifCloture');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByMotifClotureFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id) AS count, s.motifCloture')
            ->where('s.motifCloture IS NOT NULL')
            ->andWhere('s.motifCloture != \'0\'')
            ->andWhere('s.closedAt IS NOT NULL');

        $qb = $this->filteredStatisticsQuery->addFiltersToQueryBuilder($qb, $statisticsFilters);

        $qb->groupBy('s.motifCloture');

        return $qb->getQuery()->getResult();
    }
}
