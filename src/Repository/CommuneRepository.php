<?php

namespace App\Repository;

use App\Entity\Commune;
use App\Entity\Territory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commune>
 */
class CommuneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commune::class);
    }

    public function add(Commune $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Commune $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findEpciByCommuneTerritory(?Territory $territory = null, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('distinct e.code, e.nom')
            ->join('c.epci', 'e');
        if ($user && !$user->isSuperAdmin()) {
            $qb->andWhere('c.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
        }
        if (null !== $territory) {
            $qb
                ->andWhere('c.territory = :territory')
                ->setParameter('territory', $territory);
        }

        return $qb->getQuery()->getResult();
    }
}
