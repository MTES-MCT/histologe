<?php

namespace App\Repository\Behaviour;

use App\Entity\JobEvent;
use Doctrine\ORM\EntityManagerInterface;

class JobEventCleaner implements EntityCleanerRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function getClassName(): string
    {
        return JobEvent::class;
    }

    /**
     * @throws \Exception
     */
    public function cleanOlderThan(string $periodFailed = JobEvent::EXPIRATION_PERIOD_FAILED): int
    {
        $periodDefault = JobEvent::EXPIRATION_PERIOD_DEFAULT;
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->delete()
            ->andWhere('(DATE(j.createdAt) <= :created_at AND j.status = :status_failed) OR (DATE(j.createdAt) <= :created_at_default AND j.status != :status_failed)')
            ->setParameter('created_at', (new \DateTimeImmutable($periodFailed))->format('Y-m-d'))
            ->setParameter('created_at_default', (new \DateTimeImmutable($periodDefault))->format('Y-m-d'))
            ->setParameter('status_failed', JobEvent::STATUS_FAILED);

        return $queryBuilder->getQuery()->execute();
    }
}
