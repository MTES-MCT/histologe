<?php

namespace App\Repository;

use App\Entity\DesordreCritere;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DesordreCritere>
 *
 * @method DesordreCritere|null find($id, $lockMode = null, $lockVersion = null)
 * @method DesordreCritere|null findOneBy(array $criteria, array $orderBy = null)
 * @method DesordreCritere[]    findAll()
 * @method DesordreCritere[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DesordreCritereRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DesordreCritere::class);
    }

    /**
     * @return array<string, DesordreCritere>
     */
    public function findAllByZoneIndexedBySlug(string $zone): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d', 'c', 'p')
            ->leftJoin('d.desordreCategorie', 'c')
            ->leftJoin('d.desordrePrecisions', 'p')
            ->where('d.zoneCategorie = :zone')
            ->setParameter('zone', $zone);

        return $qb->indexBy('d', 'd.slugCritere')->getQuery()->getResult();
    }

    /**
     * @return DesordreCritere[]
     */
    public function findAllWithPrecisions(): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d', 'c', 'p')
            ->leftJoin('d.desordreCategorie', 'c')
            ->leftJoin('d.desordrePrecisions', 'p');

        return $qb->getQuery()->getResult();
    }

    public function findWithPrecisionsBySlug(string $slug): ?DesordreCritere
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d', 'p')
            ->leftJoin('d.desordrePrecisions', 'p')
            ->where('d.slugCritere = :slug')
            ->setParameter('slug', $slug);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
