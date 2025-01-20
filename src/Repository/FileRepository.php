<?php

namespace App\Repository;

use App\Entity\Enum\DocumentType;
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

    public function findWithOriginalAndVariants(\DateTimeInterface $limit, int $max): array
    {
        return $this->initQueryWithOriginalAndVariants($limit)->select('f')->setMaxResults($max)->getQuery()->getResult();
    }

    public function countWithOriginalAndVariants(\DateTimeInterface $limit): int
    {
        return $this->initQueryWithOriginalAndVariants($limit)->select('count(f)')->getQuery()->getSingleScalarResult();
    }

    public function updateWithOriginalAndVariants(array $ids): void
    {
        if (!empty($ids)) {
            // Étape 2 : Mettre à jour les enregistrements sélectionnés
            $this->createQueryBuilder('f')
                ->update()
                ->set('f.isOriginalDeleted', true)
                ->where('f.id IN (:ids)')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->execute();
        }
    }

    private function initQueryWithOriginalAndVariants(\DateTimeInterface $limit): QueryBuilder
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.isVariantsGenerated = true')
            ->andWhere('f.isOriginalDeleted = false')
            ->andWhere('f.createdAt < :limit')
            ->setParameter('limit', $limit);
    }

    public function findExportsOlderThan(\DateTimeInterface $limit): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.documentType = :documentType')
            ->andWhere('f.createdAt < :limit')
            ->setParameter('documentType', DocumentType::EXPORT)
            ->setParameter('limit', $limit)
            ->getQuery()->getResult();
    }
}
