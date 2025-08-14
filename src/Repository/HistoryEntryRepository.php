<?php

namespace App\Repository;

use App\Entity\HistoryEntry;
use App\Repository\Behaviour\EntityCleanerRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistoryEntry>
 *
 * @method HistoryEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method HistoryEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method HistoryEntry[]    findAll()
 * @method HistoryEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HistoryEntryRepository extends ServiceEntityRepository implements EntityCleanerRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoryEntry::class);
    }

    /**
     * @throws \Exception
     */
    public function cleanOlderThan(string $period = HistoryEntry::EXPIRATION_PERIOD): int
    {
        $queryBuilder = $this->createQueryBuilder('h');
        $queryBuilder->delete()
            ->andWhere('DATE(h.createdAt) <= :created_at')
            ->setParameter('created_at', (new \DateTimeImmutable($period))->format('Y-m-d'));

        return $queryBuilder->getQuery()->execute();
    }
}
