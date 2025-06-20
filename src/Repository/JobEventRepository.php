<?php

namespace App\Repository;

use App\Entity\Affectation;
use App\Entity\Enum\InterfacageType;
use App\Entity\Enum\PartnerType;
use App\Entity\JobEvent;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Repository\Behaviour\EntityCleanerRepositoryInterface;
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
class JobEventRepository extends ServiceEntityRepository implements EntityCleanerRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobEvent::class);
    }

    /**
     * @param array<int, int> $territories
     *
     * @return array<int, array<string, mixed>>
     */
    public function findLastJobEventByInterfacageType(
        string $type,
        int $dayPeriod,
        array $territories,
    ): array {
        $qb = $this->createQueryBuilder('j')
            ->select('j.createdAt, p.id, p.nom, s.reference, j.status, j.action, j.codeStatus, j.response')
            ->innerJoin(Signalement::class, 's', 'WITH', 's.id = j.signalementId')
            ->innerJoin(Partner::class, 'p', 'WITH', 'p.id = j.partnerId')
            ->where('j.service = :service')
            ->andWhere('j.createdAt >= :date_limit');

        if (\count($territories)) {
            $qb->andWhere('p.territory IN (:territories)')->setParameter('territories', $territories);
        }

        $qb->setParameter('service', $type)
            ->setParameter('date_limit', new \DateTimeImmutable('-'.$dayPeriod.' days'))
            ->orderBy('j.createdAt', 'DESC');

        $qb->setMaxResults(1000);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findLastEsaboraJobByPartner(
        Partner $partner,
    ): ?array {
        return $this->createQueryBuilder('j')
            ->select('MAX(j.createdAt) AS last_event')
            ->innerJoin(Partner::class, 'p', 'WITH', 'p.id = j.partnerId')
            ->where('p.id = :partner')->setParameter('partner', $partner->getId())
            ->andWhere('j.service = :service')
            ->setParameter('service', InterfacageType::ESABORA->value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return array<int, JobEvent>|null
     */
    public function findFailedEsaboraDossierByPartnerTypeByAction(
        PartnerType $partnerType,
        string $action,
    ): ?array {
        $qb = $this->createQueryBuilder('j');

        $subQuery = $this->createQueryBuilder('sub')
        ->select('MAX(sub.createdAt)')
        ->where('sub.signalementId = j.signalementId')
        ->andWhere('sub.partnerId = j.partnerId')
        ->andWhere('sub.action = :action')
        ->setParameter('action', $action)
        ->getDQL();

        $qb->where('j.status = :statusFailed')
            ->setParameter('statusFailed', JobEvent::STATUS_FAILED)
            ->andWhere('j.service = :service')
            ->setParameter('service', InterfacageType::ESABORA->value)
            ->andWhere('j.partnerType LIKE :partnerType')
            ->setParameter('partnerType', $partnerType->value)
            ->andWhere('j.action = :action')
            ->setParameter('action', $action)
            ->andWhere($qb->expr()->in(
                'j.createdAt',
                $subQuery
            ));

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws \Exception
     */
    public function cleanOlderThan(string $period = JobEvent::EXPIRATION_PERIOD): int
    {
        $queryBuilder = $this->createQueryBuilder('j');
        $queryBuilder->delete()
            ->andWhere('DATE(j.createdAt) <= :created_at')
            ->setParameter('created_at', (new \DateTimeImmutable($period))->format('Y-m-d'));

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @return array<string, int>
     */
    public function getReportEsaboraAction(string ...$actions): array
    {
        $qb = $this->createQueryBuilder('j');
        $qb->select([
            'SUM(CASE WHEN j.status = :status_success THEN 1 ELSE 0 END) AS success_count',
            'SUM(CASE WHEN j.status = :status_failed THEN 1 ELSE 0 END) AS failed_count',
        ])
            ->andWhere('j.action IN (:actions)')
            ->andWhere('DATE(j.createdAt) = :today')
            ->setParameter('actions', $actions)
            ->setParameter('today', (new \DateTimeImmutable())->format('Y-m-d'))
            ->setParameter('status_success', JobEvent::STATUS_SUCCESS)
            ->setParameter('status_failed', JobEvent::STATUS_FAILED);

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @return array<int, Affectation>
     */
    public function findFailedJobEvents(string $service, string $action): array
    {
        $qb = $this->createQueryBuilder('j');

        $subQueryBuilder = $this->createQueryBuilder('j2')
            ->select('1')
            ->where('j2.signalementId = j.signalementId')
            ->andWhere('j2.partnerId = j.partnerId')
            ->andWhere('j2.codeStatus = 200')
            ->andWhere('j2.service LIKE :service')
            ->andWhere('j2.action LIKE :action')
            ->getDQL();

        return $qb->select('a')
            ->innerJoin(Affectation::class,
                'a',
                'WITH',
                'a.signalement = j.signalementId AND a.partner = j.partnerId'
            )
            ->andWhere('j.codeStatus > 399')
            ->andWhere('j.service LIKE :service')
            ->andWhere('j.action LIKE :action')
            ->andWhere($qb->expr()->not($qb->expr()->exists($subQueryBuilder)))
            ->setParameter('service', $service)
            ->setParameter('action', $action)
            ->getQuery()
            ->getResult();
    }
}
