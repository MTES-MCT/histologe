<?php

namespace App\Repository;

use App\Entity\ServiceSecoursRoute;
use App\Service\ListFilters\SearchServiceSecoursRoute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServiceSecoursRoute>
 */
class ServiceSecoursRouteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceSecoursRoute::class);
    }

    /**
     * @return Paginator<ServiceSecoursRoute>
     */
    public function findFilteredPaginated(SearchServiceSecoursRoute $searchServiceSecoursRoute, int $maxResult): Paginator
    {
        $qb = $this->createQueryBuilder('ssr');

        if (!empty($searchServiceSecoursRoute->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchServiceSecoursRoute->getOrderType());
            $qb->orderBy($orderField, $orderDirection);
        } else {
            $qb->orderBy('ssr.name', 'ASC');
        }

        if ($searchServiceSecoursRoute->getQueryName()) {
            $qb->andWhere('LOWER(ssr.name) LIKE :queryName');
            $qb->setParameter('queryName', '%'.strtolower($searchServiceSecoursRoute->getQueryName()).'%');
        }

        $firstResult = ($searchServiceSecoursRoute->getPage() - 1) * $maxResult;
        $qb->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($qb->getQuery());
    }
}
