<?php

namespace App\Repository\Query\Statistics;

use App\Dto\StatisticsFilters;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Provider\ZipcodeProvider;
use Doctrine\ORM\EntityManagerInterface;

class MonthStatisticsQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FilteredStatisticsQuery $filteredStatisticsQuery,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByMonth(?Territory $territory, ?int $year, bool $removeImported = false): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id) AS count, MONTH(s.createdAt) AS month, YEAR(s.createdAt) AS year')
            ->where('s.statut NOT IN (:statutList)')
            ->setParameter('statutList', SignalementStatus::excludedStatuses());

        if ($removeImported) {
            $qb->andWhere('s.isImported IS NULL OR s.isImported = 0');
        }
        if ($territory && ZipcodeProvider::RHONE_CODE_DEPARTMENT_69 === $territory->getZip()) {
            $qb->innerJoin('s.territory', 't')
                ->andWhere('t.zip IN (:zipcodes)')
                ->setParameter('zipcodes', [ZipcodeProvider::RHONE_CODE_DEPARTMENT_69, ZipcodeProvider::METROPOLE_LYON_CODE_DEPARTMENT_69A]);
        } elseif ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }
        if ($year) {
            $qb->andWhere('YEAR(s.createdAt) = :year')->setParameter('year', $year);
        }

        $qb->groupBy('month')
            ->addGroupBy('year');

        $qb->orderBy('year')
            ->addOrderBy('month');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function countByMonthFiltered(StatisticsFilters $statisticsFilters): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select('COUNT(s.id) AS count, MONTH(s.createdAt) AS month, YEAR(s.createdAt) AS year');

        $qb = $this->filteredStatisticsQuery->addFiltersToQueryBuilder($qb, $statisticsFilters);

        $qb->groupBy('month')
            ->addGroupBy('year');

        $qb->orderBy('year')
            ->addOrderBy('month');

        return $qb->getQuery()->getResult();
    }
}
