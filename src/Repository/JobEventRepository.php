<?php

namespace App\Repository;

use App\Entity\JobEvent;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobEvent>
 *
 * @method JobEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method JobEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method JobEvent[]    findAll()
 * @method JobEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JobEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobEvent::class);
    }

    public function findLastJobEventByType(
        string $type,
        int $dayPeriod,
        ?Territory $territory
    ): array {
        $qb = $this->createQueryBuilder('j')
            ->select('MAX(j.createdAt) AS last_event, p.id, p.nom, s.reference, j.status, j.title')
            ->innerJoin(Signalement::class, 's', 'WITH', 's.id = j.signalementId')
            ->innerJoin(Partner::class, 'p', 'WITH', 'p.id = j.partnerId')
            ->where('j.type LIKE :type')
            ->andWhere('DATEDIFF(NOW(),j.createdAt) <= :day_period');

        if (null !== $territory) {
            $qb->andWhere('p.territory = :territory')->setParameter('territory', $territory);
        }

        $qb->setParameter('type', '%'.$type.'%')
            ->setParameter('day_period', $dayPeriod)
            ->groupBy('p.id, p.nom, s.reference,j.title, j.status')
            ->orderBy('p.id', 'ASC')
            ->addOrderBy('last_event', 'DESC');

        return $qb->getQuery()->getArrayResult();
    }
}
