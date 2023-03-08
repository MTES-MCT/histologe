<?php

namespace App\Repository;

use App\Entity\SignalementAnalytics;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SignalementAnalytics>
 *
 * @method SignalementAnalytics|null find($id, $lockMode = null, $lockVersion = null)
 * @method SignalementAnalytics|null findOneBy(array $criteria, array $orderBy = null)
 * @method SignalementAnalytics[]    findAll()
 * @method SignalementAnalytics[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SignalementAnalyticsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SignalementAnalytics::class);
    }

    public function save(SignalementAnalytics $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SignalementAnalytics $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
