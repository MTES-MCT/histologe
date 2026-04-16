<?php

namespace App\Repository\Query\Statistics;

use App\Dto\StatisticsFilters;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Territory;
use Doctrine\ORM\EntityManagerInterface;

class SituationStatisticsQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FilteredStatisticsQuery $filteredStatisticsQuery,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countBySituation(?Territory $territory, ?int $year, bool $removeImported = false): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id) AS count, sit.id, sit.menuLabel')
            ->leftJoin('s.criticites', 'criticites')
            ->leftJoin('criticites.critere', 'critere')
            ->leftJoin('critere.situation', 'sit')
            ->where('s.statut NOT IN (:statutList)')
            ->setParameter('statutList', SignalementStatus::excludedStatuses());

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }

        $qb->andWhere('sit.isActive = :isActive')->setParameter('isActive', true);

        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }
        if ($year) {
            $qb->andWhere('YEAR(s.createdAt) = :year')->setParameter('year', $year);
        }

        $qb->groupBy('sit.id');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countBySituationFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id) AS count, sit.id, sit.menuLabel')
            ->leftJoin('s.criticites', 'criticites')
            ->leftJoin('criticites.critere', 'critere')
            ->leftJoin('critere.situation', 'sit');

        $qb = $this->filteredStatisticsQuery->addFiltersToQueryBuilder($qb, $statisticsFilters);

        $qb->andWhere('sit.isActive = :isActive')->setParameter('isActive', true);
        $qb->groupBy('sit.id');

        return $qb->getQuery()->getResult();
    }
}
