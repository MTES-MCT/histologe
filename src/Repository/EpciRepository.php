<?php

namespace App\Repository;

use App\Entity\Epci;
use App\Entity\Territory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Epci>
 *
 * @method Epci|null find($id, $lockMode = null, $lockVersion = null)
 * @method Epci|null findOneBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null)
 * @method Epci[]    findAll()
 * @method Epci[]    findBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null, $limit = null, $offset = null)
 */
class EpciRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Epci::class);
    }

    /**
     * @return array<int, Epci>
     */
    public function findAllByTerritory(Territory $territory): array
    {
        $queryBuilder = $this->createQueryBuilder('e')
            ->innerJoin('e.communes', 'c')
            ->innerJoin('c.territory', 't')
            ->where('t.id = :territory')
            ->setParameter('territory', $territory);

        return $queryBuilder->getQuery()->getResult();
    }

    public function findOneByCommuneInseeAndPostalCode(?string $codeInsee, ?string $postalCode): ?Epci
    {
        if (empty($codeInsee) || empty($postalCode)) {
            return null;
        }

        return $this->createQueryBuilder('e')
            ->innerJoin('e.communes', 'c')
            ->where('c.codeInsee = :codeInsee')
            ->andWhere('c.codePostal = :postalCode')
            ->setParameter('codeInsee', $codeInsee)
            ->setParameter('postalCode', $postalCode)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
