<?php

namespace App\Repository\Query\Zone;

use App\Entity\Zone;
use App\Service\ListFilters\SearchZone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

class ZonePaginatorQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return Paginator<Zone>
     */
    public function findFilteredPaginated(SearchZone $searchZone, int $maxResult): Paginator
    {
        $qb = $this->entityManager->createQueryBuilder()->from(Zone::class, 'z');
        $qb->select('partial z.{id, name, type}', 'partial p.{id, nom}', 'partial ep.{id, nom}', 'partial t.{id, zip, name}')
            ->leftJoin('z.partners', 'p')
            ->leftJoin('z.excludedPartners', 'ep')
            ->leftJoin('z.territory', 't');

        if (!empty($searchZone->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchZone->getOrderType());
            $qb->orderBy($orderField, $orderDirection);
        } else {
            $qb->orderBy('z.name', 'ASC');
        }

        if ($searchZone->getQueryName()) {
            $qb->andWhere('LOWER(z.name) LIKE :queryName');
            $qb->setParameter('queryName', '%'.strtolower($searchZone->getQueryName()).'%');
        }
        if ($searchZone->getTerritory()) {
            $qb->andWhere('z.territory = :territory')->setParameter('territory', $searchZone->getTerritory());
        } elseif (!$searchZone->getUser()->isSuperAdmin()) {
            $qb->andWhere('z.territory IN (:territories)')->setParameter('territories', $searchZone->getUser()->getPartnersTerritories());
        }
        if ($searchZone->getType()) {
            $qb->andWhere('z.type = :type')->setParameter('type', $searchZone->getType()->value);
        }

        $firstResult = ($searchZone->getPage() - 1) * $maxResult;
        $qb->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($qb->getQuery());
    }
}
