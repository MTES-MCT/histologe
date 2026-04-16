<?php

namespace App\Repository\Query\Statistics;

use App\Dto\StatisticsFilters;
use App\Entity\Signalement;
use Doctrine\ORM\EntityManagerInterface;

class CriticiteStatisticsQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FilteredStatisticsQuery $filteredStatisticsQuery,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByCriticiteFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id) AS count, crit.id, crit.label')
            ->leftJoin('s.criticites', 'crit');

        $qb = $this->filteredStatisticsQuery->addFiltersToQueryBuilder($qb, $statisticsFilters);

        $qb->andWhere('crit.isArchive = :isArchive')->setParameter('isArchive', false);
        $qb->groupBy('crit.id');

        return $qb->getQuery()->getResult();
    }
}
