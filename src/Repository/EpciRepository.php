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
 * @method Epci|null findOneBy(array $criteria, array $orderBy = null)
 * @method Epci[]    findAll()
 * @method Epci[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EpciRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Epci::class);
    }

    public function findCommunesByEpcis(array $epciCodes): array
    {
        $queryBuilder = $this->createQueryBuilder('e')
            ->select('DISTINCT c.nom, c.codePostal')
            ->innerJoin('e.communes', 'c')
            ->where('e.code IN (:epci_codes)')
            ->setParameter('epci_codes', $epciCodes);

        return $queryBuilder->getQuery()->getArrayResult();
    }

    public function findAllByTerritory(Territory $territory): array
    {
        $queryBuilder = $this->createQueryBuilder('e')
            ->innerJoin('e.communes', 'c')
            ->innerJoin('c.territory', 't')
            ->where('t.id = :territory')
            ->setParameter('territory', $territory);

        return $queryBuilder->getQuery()->getResult();
    }
}
