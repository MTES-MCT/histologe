<?php

namespace App\Repository\Query\Statistics;

use App\Dto\StatisticsFilters;
use App\Entity\Signalement;
use Doctrine\ORM\EntityManagerInterface;

class VisiteStatisticsQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FilteredStatisticsQuery $filteredStatisticsQuery,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByVisiteFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id) as count')
            ->addSelect(
                'case
                when i.id IS NULL then \'Non\'
                else \'Oui\'
                end as visite'
            )
            ->leftJoin('s.interventions', 'i');

        $qb = $this->filteredStatisticsQuery->addFiltersToQueryBuilder($qb, $statisticsFilters);

        $qb->groupBy('visite');

        return $qb->getQuery()->getResult();
    }
}
