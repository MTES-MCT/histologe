<?php

namespace App\Repository;

use App\Entity\File;
use App\Entity\Territory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<File>
 *
 * @method File|null find($id, $lockMode = null, $lockVersion = null)
 * @method File|null findOneBy(array $criteria, array $orderBy = null)
 * @method File[]    findAll()
 * @method File[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, File::class);
    }

    public function save(File $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(File $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getPhotosWihoutVariants(?Territory $territory = null)
    {
        $qb = $this->createQueryBuilder('f')
            ->select('f')
            ->innerJoin('f.signalement', 's')
            ->where('f.fileType = :type')
            ->andWhere('f.isVariantsGenerated = false')
            ->setParameter('type', File::FILE_TYPE_PHOTO);
        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }

        return $qb->getQuery()->getResult();
    }

    public function findWithOriginalAndVariants(int $max): array
    {
        return $this->initQueryWithOriginalAndVariants()->select('f')->setMaxResults($max)->getQuery()->getResult();
    }

    public function countWithOriginalAndVariants(): int
    {
        return $this->initQueryWithOriginalAndVariants()->select('count(f)')->getQuery()->getSingleScalarResult();
    }

    private function initQueryWithOriginalAndVariants(): QueryBuilder
    {
        $limit = (new \DateTime())->modify('-1 month');

        return $this->createQueryBuilder('f')
            ->andWhere('f.isVariantsGenerated = true')
            ->andWhere('f.isOriginalDeleted = false')
            ->andWhere('f.createdAt < :limit')
            ->setParameter('limit', $limit);
    }
}
