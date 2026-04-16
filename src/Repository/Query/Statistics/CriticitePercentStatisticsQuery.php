<?php

namespace App\Repository\Query\Statistics;

use App\Dto\StatisticsFilters;
use App\Entity\Signalement;
use App\Service\Statistics\CriticitePercentStatisticProvider;
use Doctrine\ORM\EntityManagerInterface;

class CriticitePercentStatisticsQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FilteredStatisticsQuery $filteredStatisticsQuery,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByCriticitePercentFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id) as count')
            ->addSelect('case
                when s.score >= 0 and s.score < 10 then \''.CriticitePercentStatisticProvider::CRITICITE_VERY_WEAK.'\'
                when s.score >= 10 and s.score < 30 then \''.CriticitePercentStatisticProvider::CRITICITE_WEAK.'\'
                else \''.CriticitePercentStatisticProvider::CRITICITE_STRONG.'\'
                end as range');

        $qb = $this->filteredStatisticsQuery->addFiltersToQueryBuilder($qb, $statisticsFilters);

        $qb->groupBy('range');

        return $qb->getQuery()->getResult();
    }
}
