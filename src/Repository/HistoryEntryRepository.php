<?php

namespace App\Repository;

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
}
