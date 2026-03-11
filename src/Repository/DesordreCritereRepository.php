<?php

namespace App\Repository;

use App\Entity\DesordreCritere;
use App\Entity\Enum\AppContext;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
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
     *
     * @throws QueryException
     */
    public function findAllByZoneIndexedBySlug(string $zone): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d', 'c', 'p')
            ->leftJoin('d.desordreCategorie', 'c')
            ->leftJoin('d.desordrePrecisions', 'p')
            ->where('d.zoneCategorie = :zone')
            ->andWhere('c.appContext = :appContext')
            ->setParameter('zone', $zone)
            ->setParameter('appContext', AppContext::DEFAULT);

        return $qb->indexBy('d', 'd.slugCritere')->getQuery()->getResult();
    }

    /**
     * @return DesordreCritere[]
     */
    public function findAllWithPrecisions(AppContext $appContext = AppContext::DEFAULT): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d', 'c', 'p')
            ->leftJoin('d.desordreCategorie', 'c')
            ->leftJoin('d.desordrePrecisions', 'p')
            ->where('c.appContext = :appContext')
            ->setParameter('appContext', $appContext);

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findWithPrecisionsBySlug(string $slug, AppContext $appContext = AppContext::DEFAULT): ?DesordreCritere
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d', 'p', 'c')
            ->leftJoin('d.desordrePrecisions', 'p')
            ->where('d.slugCritere = :slug')
            ->andWhere('c.appContext = :appContext')
            ->leftJoin('d.desordreCategorie', 'c')
            ->setParameter('slug', $slug)
            ->setParameter('appContext', $appContext);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return array<string, DesordreCritere>
     */
    public function findAllByAppContext(AppContext $appContext = AppContext::DEFAULT): array
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.desordreCategorie', 'c')
            ->where('c.appContext = :appContext')
            ->setParameter('appContext', $appContext);

        return $qb->getQuery()->getResult();
    }
}
