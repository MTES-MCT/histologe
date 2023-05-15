<?php

namespace App\Repository;

use App\Entity\Enum\InterfacageType;
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

    public function findLastJobEventByInterfacageType(
        string $type,
        int $dayPeriod,
        ?Territory $territory,
    ): array {
        $qb = $this->createQueryBuilder('j')
            ->select('MAX(j.createdAt) AS last_event, p.id, p.nom, s.reference, j.status, j.action, j.codeStatus')
            ->innerJoin(Signalement::class, 's', 'WITH', 's.id = j.signalementId')
            ->innerJoin(Partner::class, 'p', 'WITH', 'p.id = j.partnerId')
            ->where('j.service LIKE :service')
            ->andWhere('DATEDIFF(NOW(),j.createdAt) <= :day_period');

        if ($dayPeriod) {
            $qb->andWhere('DATEDIFF(NOW(),j.createdAt) <= :day_period')
                ->setParameter('day_period', $dayPeriod);
        }
        if (null !== $territory) {
            $qb->andWhere('p.territory = :territory')->setParameter('territory', $territory);
        }

        $qb->setParameter('service', '%'.$type.'%')
            ->setParameter('day_period', $dayPeriod)
            ->groupBy('p.id, p.nom, s.reference, j.action, j.status, j.codeStatus, j.response')
            ->orderBy('last_event', 'DESC');

        return $qb->getQuery()->getArrayResult();
    }

    public function findLastEsaboraJobByPartner(
        Partner $partner
    ): ?array {
        return $this->createQueryBuilder('j')
            ->select('MAX(j.createdAt) AS last_event')
            ->innerJoin(Partner::class, 'p', 'WITH', 'p.id = j.partnerId')
            ->where('p.id = :partner')->setParameter('partner', $partner->getId())
            ->andWhere('j.service LIKE :service')
            ->setParameter('service', '%'.InterfacageType::ESABORA->value.'%')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
