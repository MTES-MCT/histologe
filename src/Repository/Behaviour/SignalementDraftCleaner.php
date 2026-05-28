<?php

namespace App\Repository\Behaviour;

use App\Entity\Enum\SignalementDraftStatus;
use App\Entity\SignalementDraft;
use Doctrine\ORM\EntityManagerInterface;

class SignalementDraftCleaner implements EntityCleanerRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function getClassName(): string
    {
        return SignalementDraft::class;
    }

    /**
     * @throws \Exception
     */
    public function cleanOlderThan(string $period = SignalementDraft::EXPIRATION_PERIOD): int
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->delete()
            ->from(SignalementDraft::class, 's')
            ->andWhere('s.status IN (:statuses)')
            ->andWhere('DATE(s.createdAt) <= :created_at')
            ->setParameter('statuses', [SignalementDraftStatus::EN_COURS, SignalementDraftStatus::ARCHIVE])
            ->setParameter('created_at', (new \DateTimeImmutable($period))->format('Y-m-d'));

        return $queryBuilder->getQuery()->execute();
    }
}
