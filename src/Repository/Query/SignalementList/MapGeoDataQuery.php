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
            address.housenumber,
            address.street,
            address.postCode,
            address.city,
            address.point,
            s.reference,
            s.score,
            s.nomOccupant,
            s.prenomOccupant,
            s.uuid,
            s.details, 
            s.geoloc')
            ->andWhere("(JSON_EXTRACT(s.geoloc,'$.lat') != '' AND JSON_EXTRACT(s.geoloc,'$.lng') != '') OR address.point IS NOT NULL")
            ->setMaxResults(self::MARKERS_PAGE_SIZE);

        $signalements = $qb->getQuery()->getArrayResult();

        foreach ($signalements as $key => $signalement) {
            if (!empty($signalement['point']) && empty($signalement['geoloc'])) {
                $signalement['geoloc'] = [
                    'lat' => $signalement['point']->getY(),
                    'lng' => $signalement['point']->getX(),
                ];
            }
            $signalements[$key] = $signalement;
        }

        return $signalements;
    }
}
