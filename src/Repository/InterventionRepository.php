<?php

namespace App\Repository;

use App\Entity\Enum\InterventionType;
use App\Entity\Intervention;
use App\Entity\Signalement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Intervention>
 *
 * @method Intervention|null find($id, $lockMode = null, $lockVersion = null)
 * @method Intervention|null findOneBy(array $criteria, array $orderBy = null)
 * @method Intervention[]    findAll()
 * @method Intervention[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InterventionRepository extends ServiceEntityRepository
{
    private const int NB_DAYS_DELAY_NOTIFICATION_VISIT_PAST = 2;
    private const int NB_DAYS_DELAY_NOTIFICATION_VISIT_FUTURE = -2;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Intervention::class);
    }

    public function save(Intervention $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Intervention $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array<int, Intervention>
     */
    public function getVisitsToNotify(int $delay): array
    {
        $queryBuilder = $this->createQueryBuilder('i');

        return $queryBuilder
            ->where('i.status = :planned')
            ->setParameter('planned', Intervention::STATUS_PLANNED)
            ->andWhere('i.type = :visite')
            ->setParameter('visite', InterventionType::VISITE->name)
            ->andWhere('DATEDIFF(CURRENT_DATE(),i.scheduledAt) = :day_delay')
            ->setParameter('day_delay', $delay)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Intervention>
     */
    public function getFutureVisits(): array
    {
        return $this->getVisitsToNotify(self::NB_DAYS_DELAY_NOTIFICATION_VISIT_FUTURE);
    }

    /**
     * @return array<int, Intervention>
     */
    public function getPastVisits(): array
    {
        return $this->getVisitsToNotify(self::NB_DAYS_DELAY_NOTIFICATION_VISIT_PAST);
    }

    /**
     * @return array<int, Intervention>
     */
    public function getOrderedVisitesForSignalement(Signalement $signalement): array
    {
        $queryBuilder = $this->createQueryBuilder('i');

        return $queryBuilder
            ->andWhere('i.type IN (:visiteTypes)')
            ->setParameter('visiteTypes', [InterventionType::VISITE->name, InterventionType::VISITE_CONTROLE->name])
            ->andWhere('i.signalement = :signalement')
            ->setParameter('signalement', $signalement)
            ->orderBy('i.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Intervention>
     */
    public function getPendingVisitesForSignalement(Signalement $signalement): array
    {
        $queryBuilder = $this->createQueryBuilder('i');

        return $queryBuilder
            ->where('i.status = :planned')
            ->setParameter('planned', Intervention::STATUS_PLANNED)
            ->andWhere('i.type = :visite')
            ->setParameter('visite', InterventionType::VISITE->name)
            ->andWhere('i.signalement = :signalement')
            ->setParameter('signalement', $signalement)
            ->getQuery()
            ->getResult();
    }
}
