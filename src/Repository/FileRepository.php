<?php

namespace App\Repository;

use App\Entity\File;
use App\Entity\Territory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function findWithOriginalAndVariants($count = false)
    {
        $limit = (new \DateTime())->modify('-1 month');
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.isVariantsGenerated = true')
            ->andWhere('f.isOriginalDeleted = false')
            ->andWhere('f.createdAt < :limit')
            ->setParameter('limit', $limit);
        if ($count) {
            return $qb->select('count(f)')->getQuery()->getSingleScalarResult();
        }

        return $qb->select('f')->setMaxResults(2500)->getQuery()->getResult();
    }
}
