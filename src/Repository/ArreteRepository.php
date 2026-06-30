<?php

namespace App\Repository;

use App\Entity\Arrete;
use App\Service\ListFilters\SearchArrete;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Arrete>
 */
class ArreteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Arrete::class);
    }

    /**
     * @return Paginator<Arrete>
     */
    public function findFilteredPaginated(SearchArrete $searchArrete, int $maxResult): Paginator
    {
        $qb = $this->createQueryBuilder('a');
        $qb->innerJoin('a.address', 'address');

        if ($searchArrete->getTerritory()) {
            $qb->andWhere('address.territory = :territory')->setParameter('territory', $searchArrete->getTerritory());
        } elseif (!$searchArrete->getUser()->isSuperAdmin()) {
            $qb->andWhere('address.territory IN (:territories)')->setParameter('territories', $searchArrete->getUser()->getPartnersTerritories());
        }

        if ($searchArrete->getHousenumber()) {
            $qb->andWhere('address.housenumber = :housenumber')->setParameter('housenumber', $searchArrete->getHousenumber());
        }
        if ($searchArrete->getStreet()) {
            $qb->andWhere('address.street = :street')->setParameter('street', $searchArrete->getStreet());
        }
        if ($searchArrete->getPostCode()) {
            $qb->andWhere('address.postCode = :postCode')->setParameter('postCode', $searchArrete->getPostCode());
        }
        if ($searchArrete->getCity()) {
            $qb->andWhere('address.city = :city')->setParameter('city', $searchArrete->getCity());
        }
        if ($searchArrete->getCityCode()) {
            $qb->andWhere('address.cityCode = :cityCode')->setParameter('cityCode', $searchArrete->getCityCode());
        }

        if ($searchArrete->getTypeArretes()) {
            $qb->andWhere('a.typeArrete IN (:typeArretes)')->setParameter('typeArretes', $searchArrete->getTypeArretes());
        }

        if (null !== $searchArrete->getMainLevee()) {
            $qb->andWhere('a.mainLevee = :mainLevee')->setParameter('mainLevee', $searchArrete->getMainLevee());
        }

        if ($searchArrete->getOrderType()) {
            [$orderField, $orderDirection] = explode('-', $searchArrete->getOrderType());
            $qb->orderBy($orderField, $orderDirection);
        } else {
            $qb->orderBy('a.dateArrete', 'ASC');
        }

        $firstResult = ($searchArrete->getPage() - 1) * $maxResult;
        $qb->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($qb->getQuery(), true);
    }
}
