<?php

namespace App\Repository\Query\SignalementList;

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
    public function getData(User $user, array $options): array
    {
        $qb = $this->queryBuilderFactory->create($user, $options);

        $qb->addSelect('
            s.statut,
            s.adresseOccupant,
            s.cpOccupant,
            s.villeOccupant,
            s.reference,
            s.score,
            s.nomOccupant,
            s.prenomOccupant,
            s.uuid,
            s.details, 
            s.geoloc')
            ->andWhere("JSON_EXTRACT(s.geoloc,'$.lat') != ''")
            ->andWhere("JSON_EXTRACT(s.geoloc,'$.lng') != ''")
            ->setMaxResults(self::MARKERS_PAGE_SIZE);

        return $qb->getQuery()->getArrayResult();
    }
}
