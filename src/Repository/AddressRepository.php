<?php

namespace App\Repository;

use App\Entity\Address;
use App\Entity\Territory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Address>
 */
class AddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }

    /**
     * @return array<int, mixed>
     */
    public function findAllList(?Territory $territory = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.id, CONCAT(a.housenumber, \' \', a.street) as address')
            ->orderBy('a.street', 'ASC')
            ->addOrderBy('a.housenumber', 'ASC');

        if ($territory) {
            $qb->andWhere('a.territory = :territory')
                ->setParameter('territory', $territory);
        }

        return $qb->getQuery()->getArrayResult();
    }
}
