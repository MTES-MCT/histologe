<?php

namespace App\Repository;

use App\Entity\Tag;
use App\Entity\Territory;
use App\Service\ListFilters\SearchTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Tag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tag[]    findAll()
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function findAllActiveInTerritories(
        array $territories,
    ): mixed {
        $qb = $this->createQueryBuilder('t');
        $qb->andWhere('t.isArchive != 1')->orderBy('t.label', 'ASC');
        if (count($territories)) {
            $qb->andWhere('t.territory IN (:territories)')->setParameter('territories', $territories);
        }

        return $qb->getQuery()->getResult();
    }

    public function findAllActive(
        ?Territory $territory = null,
    ): mixed {
        $qb = $this->createQueryBuilder('t');
        $qb->andWhere('t.isArchive != 1')
            ->orderBy('t.label', 'ASC')
            ->indexBy('t', 't.id');
        if ($territory) {
            $qb->andWhere('t.territory = :territory')
                ->setParameter('territory', $territory);
        }

        return $qb->getQuery()
            ->getResult();
    }

    public function findFilteredPaginated(SearchTag $searchTag, int $maxResult): Paginator
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t', 's')
            ->leftJoin('t.signalements', 's', 'WITH', 's.statut != 7')
            ->andWhere('t.isArchive != 1');

        if (!empty($searchTag->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchTag->getOrderType());
            $qb->orderBy($orderField, $orderDirection);
        } else {
            $qb->orderBy('t.label', 'ASC');
        }

        $qb->indexBy('t', 't.id');
        if ($searchTag->getTerritory()) {
            $qb->andWhere('t.territory = :territory')
                ->setParameter('territory', $searchTag->getTerritory());
        }
        if ($searchTag->getQueryTag()) {
            $qb->andWhere('t.label LIKE :search')
                ->setParameter('search', '%'.$searchTag->getQueryTag().'%');
        }

        $firstResult = ($searchTag->getPage() - 1) * $maxResult;
        $qb->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($qb->getQuery(), true);
    }
}
