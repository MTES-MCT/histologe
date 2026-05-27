<?php

namespace App\Repository;

use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\HistoryEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistoryEntry>
 *
 * @method HistoryEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method HistoryEntry|null findOneBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null)
 * @method HistoryEntry[]    findAll()
 * @method HistoryEntry[]    findBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null, $limit = null, $offset = null)
 */
class HistoryEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoryEntry::class);
    }

    public function findFirstEntityUpdateAfter(int $entityId, string $entityName, \DateTimeImmutable $afterDate): ?HistoryEntry
    {
        return $this->createQueryBuilder('h')
            ->where('h.entityId = :entityId')
            ->andWhere('h.entityName = :entityName')
            ->andWhere('h.event = :event')
            ->andWhere('h.createdAt > :date')
            ->setParameter('entityId', $entityId)
            ->setParameter('entityName', $entityName)
            ->setParameter('event', HistoryEntryEvent::UPDATE)
            ->setParameter('date', $afterDate)
            ->orderBy('h.createdAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
