<?php

namespace App\Repository\Query\SignalementList;

use App\Entity\Enum\SignalementStatus;
use App\Entity\User;
use Doctrine\DBAL\Exception;

class MapGeoDataQuery
{
    public const int MARKERS_PAGE_SIZE = 9000;

    public function __construct(private readonly QueryBuilderFactory $queryBuilderFactory)
    {
    }

    /**
     * @param array<string,mixed> $options
     *
     * @return array<int, array<string,mixed>>
     *
     * @throws Exception
     */
    public function getData(User $user, array $options, int $offset): array
    {
        $qb = $this->queryBuilderFactory->create($user, $options);

        $qb->addSelect('s.geoloc, s.details, s.cpOccupant, s.inseeOccupant')
            ->andWhere("JSON_EXTRACT(s.geoloc,'$.lat') != ''")
            ->andWhere("JSON_EXTRACT(s.geoloc,'$.lng') != ''")
            ->andWhere('s.statut NOT IN (:signalement_status_list)')
            ->setParameter('signalement_status_list', SignalementStatus::excludedStatuses())
            ->setFirstResult($firstResult)
            ->setMaxResults(self::MARKERS_PAGE_SIZE);

        return $qb->getQuery()->getArrayResult();
    }
}
