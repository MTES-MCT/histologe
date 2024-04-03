<?php

namespace App\Repository;

use App\Entity\Enum\Qualification;
use App\Entity\SignalementQualification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SignalementQualification>
 *
 * @method SignalementQualification|null find($id, $lockMode = null, $lockVersion = null)
 * @method SignalementQualification|null findOneBy(array $criteria, array $orderBy = null)
 * @method SignalementQualification[]    findAll()
 * @method SignalementQualification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SignalementQualificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SignalementQualification::class);
    }

    public function save(SignalementQualification $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SignalementQualification $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findSignalementsByQualification(Qualification $qualification, ?array $statuses = null)
    {
        $queryBuilder = $this->createQueryBuilder('sq')
            ->select('s.id')
            ->innerJoin('sq.signalement', 's')
            ->where('sq.qualification LIKE :qualification')
            ->setParameter('qualification', $qualification);

        if (!empty($statuses)) {
            $queryBuilder
                ->andWhere('sq.status IN (:statuses)')
                ->setParameter('statuses', $statuses);
        }

        $queryBuilder->distinct();

        return $queryBuilder->getQuery()->getResult();
    }
}
