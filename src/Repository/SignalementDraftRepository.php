<?php

namespace App\Repository;

use App\Entity\Enum\SignalementDraftStatus;
use App\Entity\SignalementDraft;
use App\Repository\Behaviour\EntityCleanerRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Clock\ClockAwareTrait;

/**
 * @extends ServiceEntityRepository<SignalementDraft>
 *
 * @method SignalementDraft|null find($id, $lockMode = null, $lockVersion = null)
 * @method SignalementDraft|null findOneBy(array $criteria, array $orderBy = null)
 * @method SignalementDraft[]    findAll()
 * @method SignalementDraft[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SignalementDraftRepository extends ServiceEntityRepository implements EntityCleanerRepositoryInterface
{
    use ClockAwareTrait;

    private const string LOCK_SCREEN_FEATURE_START_AT = '2025-06-13 12:00';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SignalementDraft::class);
    }

    public function save(SignalementDraft $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SignalementDraft $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @throws \Exception
     */
    public function cleanOlderThan(string $period = SignalementDraft::EXPIRATION_PERIOD): int
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->delete()
            ->andWhere('s.status IN (:statuses)')
            ->andWhere('DATE(s.createdAt) <= :created_at')
            ->setParameter('statuses', [SignalementDraftStatus::EN_COURS, SignalementDraftStatus::ARCHIVE])
            ->setParameter('created_at', (new \DateTimeImmutable($period))->format('Y-m-d'));

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @return array<int, SignalementDraft>
     *
     * @throws \DateMalformedStringException
     */
    public function findPendingBlockedBailLast3Months(): array
    {
        $limitDate = $this->now()
            ->modify('-3 months')
            ->format('Y-m-d');

        $queryBuilder = $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.currentStep = :current_step')
            ->andWhere('s.updatedAt >= :lock_screen_feature_start_at')
            ->andWhere('DATE(s.bailleurPrevenuAt) <= :bailleur_prevenu_at')
            ->andWhere('s.pendingDraftRemindedAt is NULL')
            ->setParameter('status', SignalementDraftStatus::EN_COURS)
            ->setParameter('current_step', 'info_procedure_bail')
            ->setParameter('lock_screen_feature_start_at', self::LOCK_SCREEN_FEATURE_START_AT)
            ->setParameter('bailleur_prevenu_at', $limitDate);

        return $queryBuilder->getQuery()->execute();
    }
}
