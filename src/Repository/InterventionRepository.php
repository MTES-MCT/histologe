<?php

namespace App\Repository;

use App\Entity\Enum\InterventionType;
use App\Entity\Intervention;
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
    private const NB_DAYS_DELAY_NOTIFICATION_VISIT_PAST = 2;
    private const NB_DAYS_DELAY_NOTIFICATION_VISIT_FUTURE = -2;

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

    public function getVisitsToNotify(int $delay): array
    {
        $queryBuilder = $this->createQueryBuilder('i');

        return $queryBuilder
            ->where('i.status = :planned')
            ->setParameter('planned', Intervention::STATUS_PLANNED)
            ->andWhere('i.type = :visite')
            ->setParameter('visite', InterventionType::VISITE->name)
            ->andWhere('DATEDIFF(CURRENT_DATE(),i.date) = :day_delay')
            ->setParameter('day_delay', $delay)
            ->getQuery()
            ->getResult();
    }

    public function getFutureVisits(): array
    {
        return $this->getVisitsToNotify(self::NB_DAYS_DELAY_NOTIFICATION_VISIT_FUTURE);
    }

    public function getPastVisits(): array
    {
        return $this->getVisitsToNotify(self::NB_DAYS_DELAY_NOTIFICATION_VISIT_PAST);
    }
}
