<?php

namespace App\Repository;

use App\Entity\Arrete;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
