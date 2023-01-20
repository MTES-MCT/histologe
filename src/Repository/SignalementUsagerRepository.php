<?php

namespace App\Repository;

use App\Entity\SignalementUsager;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SignalementUsager>
 *
 * @method SignalementUsager|null find($id, $lockMode = null, $lockVersion = null)
 * @method SignalementUsager|null findOneBy(array $criteria, array $orderBy = null)
 * @method SignalementUsager[]    findAll()
 * @method SignalementUsager[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SignalementUsagerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SignalementUsager::class);
    }

    public function save(SignalementUsager $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SignalementUsager $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
