<?php

namespace App\Repository;

use App\Entity\Address;
use App\Entity\Commune;
use App\Service\Gouv\Ban\Response\BanAddress;
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

    public function findForBanAddress(BanAddress $banAddress): ?Address
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.banId = :banId')->setParameter('banId', $banAddress->getBanId());
        if ($banAddress->getHousenumber()) {
            $qb->orWhere('a.housenumber = :housenumber AND a.street = :street AND a.postCode = :postCode AND a.cityCode = :cityCode')
                ->setParameter('housenumber', $banAddress->getHousenumber());
        } else {
            $qb->orWhere('a.housenumber IS NULL AND a.street = :street AND a.postCode = :postCode AND a.cityCode = :cityCode');
        }
        $qb->setParameter('street', $banAddress->getStreet())
            ->setParameter('postCode', $banAddress->getZipCode())
            ->setParameter('cityCode', $banAddress->getInseeCode());
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findForManualAddress(?string $housenumber, string $street, string $postCode, string $cityCode): ?Address
    {
        $qb = $this->createQueryBuilder('a');
        if ($housenumber) {
            $qb->where('a.housenumber = :housenumber')->setParameter('housenumber', $housenumber);
        } else {
            $qb->where('a.housenumber IS NULL');
        }
        $qb->andWhere('a.street = :street')->setParameter('street', $street);
        $qb->andWhere('a.postCode = :postCode')->setParameter('postCode', $postCode);
        $qb->andWhere('a.cityCode = :cityCode')->setParameter('cityCode', $cityCode);
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return Address[]
     */
    public function findWithInconsistentCommuneName(Commune $commune): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.cityCode = :communeCode')->setParameter('communeCode', $commune->getCodeInsee());
        $qb->andWhere('a.city != :communeName')->setParameter('communeName', $commune->getNom());

        return $qb->getQuery()->getResult();
    }
}
