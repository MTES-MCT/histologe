<?php

namespace App\Repository;

use App\Entity\Zone;
use App\Service\SearchZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Zone>
 */
class ZoneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Zone::class);
    }

    public function findFilteredPaginated(SearchZone $searchZone, int $maxResult): Paginator
    {
        $qb = $this->createQueryBuilder('z');
        $qb->select('z', 'p', 't')
            ->leftJoin('z.partners', 'p')
            ->leftJoin('z.territory', 't')
            ->orderBy('z.name', 'ASC');

        if ($searchZone->getQueryName()) {
            $qb->andWhere('LOWER(z.name) LIKE :queryName');
            $qb->setParameter('queryName', '%'.strtolower($searchZone->getQueryName()).'%');
        }
        if ($searchZone->getTerritory()) {
            $qb->andWhere('z.territory = :territory')->setParameter('territory', $searchZone->getTerritory());
        }

        $firstResult = ($searchZone->getPage() - 1) * $maxResult;
        $qb->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($qb->getQuery());
    }
}
