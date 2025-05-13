<?php

namespace App\Repository;

use App\Entity\Enum\SignalementDraftStatus;
use App\Entity\SignalementDraft;
use App\Repository\Behaviour\EntityCleanerRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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

    public function findPendingBlockedBailLast3Months(): array
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.currentStep = :current_step')
            ->andWhere('DATE(s.updatedAt) >= :min_updated_at')
            ->setParameter('status', SignalementDraftStatus::EN_COURS)
            ->setParameter('current_step', 'info_procedure_bail')
            ->setParameter('min_updated_at', (new \DateTimeImmutable('- 3 months'))->format('Y-m-d'));

        return $queryBuilder->getQuery()->execute();
    }
}
