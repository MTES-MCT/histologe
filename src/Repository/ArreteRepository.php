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
            $qb->andWhere($searchArrete->getMainLevee() ? 'a.dateMainLevee IS NOT NULL' : 'a.dateMainLevee IS NULL');
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

    /**
     * @return Arrete[]
     */
    public function findByBanId(string $banId): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.address', 'addr')
            ->where('addr.banId = :banId')
            ->setParameter('banId', $banId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<int, string>|string $housenumber
     *
     * @return Arrete[]
     */
    public function findByAddress(
        array|string|null $housenumber,
        string $street,
        string $postCode,
        string $cityCode, // codeInsee
    ): array {
        $qb = $this->createQueryBuilder('a')
            ->join('a.address', 'addr')
            ->where('addr.street = :street')
            ->andWhere('addr.postCode = :postCode')
            ->andWhere('addr.cityCode = :cityCode')
            ->setParameter('street', $street)
            ->setParameter('postCode', $postCode)
            ->setParameter('cityCode', $cityCode);

        if (null === $housenumber) {
            $qb->andWhere('addr.housenumber IS NULL');
        } elseif (\is_array($housenumber)) {
            $qb->andWhere('addr.housenumber IN (:housenumber)')
                ->setParameter('housenumber', $housenumber);
        } else {
            $qb->andWhere('addr.housenumber = :housenumber')
                ->setParameter('housenumber', $housenumber);
        }

        return $qb->getQuery()->getResult();
    }
}
