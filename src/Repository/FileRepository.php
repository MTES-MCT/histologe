<?php

namespace App\Repository;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\FileVisibilityService;
use App\Service\ListFilters\SearchTerritoryFiles;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
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
    public function __construct(
        ManagerRegistry $registry,
        private readonly FileVisibilityService $fileVisibilityService,
    ) {
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

    /**
     * @return array<int, File>
     */
    public function getPhotosWihoutVariants(?Territory $territory = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->select('f')
            ->innerJoin('f.signalement', 's')
            ->where('f.extension IN :extensionsImage')
            ->andWhere('f.isVariantsGenerated = false')
            ->setParameter('extensionsImage', File::RESIZABLE_EXTENSION);
        if ($territory) {
            $qb->andWhere('s.territory = :territory')->setParameter('territory', $territory);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, File>
     */
    public function findWithOriginalAndVariants(\DateTimeInterface $limit, int $max): array
    {
        return $this->initQueryWithOriginalAndVariants($limit)->select('f')->setMaxResults($max)->getQuery()->getResult();
    }

    public function countWithOriginalAndVariants(\DateTimeInterface $limit): int
    {
        return $this->initQueryWithOriginalAndVariants($limit)->select('count(f)')->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array<int, int> $ids
     */
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

    /**
     * @return array<int, File>
     */
    public function findExportsOlderThan(\DateTimeInterface $limit): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.documentType = :documentType')
            ->andWhere('f.createdAt < :limit')
            ->setParameter('documentType', DocumentType::EXPORT)
            ->setParameter('limit', $limit)
            ->getQuery()->getResult();
    }

    public function updateIsWaitingSuiviForSignalement(Signalement $signalement): void
    {
        $this->createQueryBuilder('f')
            ->update()
            ->set('f.isWaitingSuivi', ':isWaitingSuivi')
            ->where('f.signalement = :signalement')
            ->setParameter('isWaitingSuivi', false)
            ->setParameter('signalement', $signalement)
            ->getQuery()
            ->execute();
    }

    /**
     * @return array<int, File>
     */
    public function findTempForSignalementAndUserIndexedById(Signalement $signalement, User $user): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.signalement = :signalement')
            ->andWhere('f.isTemp = true')
            ->andWhere('f.uploadedBy = :user')
            ->setParameter('signalement', $signalement)
            ->setParameter('user', $user)
            ->indexBy('f', 'f.id')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<string, Territory> $territories
     *
     * @return Paginator|array<string, array<File>|int>
     */
    public function findFilteredPaginated(
        SearchTerritoryFiles $searchTerritoryFiles,
        ?array $territories,
        int $maxListPagination,
        ?User $user = null,
    ): Paginator|array {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.isStandalone = true')
            ->andWhere('f.signalement IS NULL');

        if ($searchTerritoryFiles->getQueryName()) {
            $qb->andWhere('f.title LIKE :queryName')
                ->setParameter('queryName', '%'.$searchTerritoryFiles->getQueryName().'%');
        }

        if (!empty($searchTerritoryFiles->getTerritory())) {
            $qb->andWhere('f.territory = :territory')
                ->setParameter('territory', $searchTerritoryFiles->getTerritory());
        } elseif (!empty($territories)) {
            $qb->andWhere('f.territory IN (:territories)')
                ->setParameter('territories', $territories);
        }

        if ($searchTerritoryFiles->getType()) {
            $qb->andWhere('f.documentType = :documentType')
                ->setParameter('documentType', $searchTerritoryFiles->getType());
        }
        if ($searchTerritoryFiles->getOrderType()) {
            [$field, $direction] = explode('-', $searchTerritoryFiles->getOrderType());
            $qb->orderBy($field, $direction);
        } else {
            $qb->orderBy('f.title', 'ASC');
        }

        if (null === $user) {
            $qb->setFirstResult(($searchTerritoryFiles->getPage() - 1) * $maxListPagination)
                ->setMaxResults($maxListPagination);

            return new Paginator($qb->getQuery());
        }

        // Récupère tous les fichiers correspondant aux filtres classiques
        $allFiles = $qb->getQuery()->getResult();

        $filteredFiles = $this->fileVisibilityService->filterFilesForUser($allFiles, $user);
        // Pagination PHP
        $page = max(1, $searchTerritoryFiles->getPage());
        $offset = ($page - 1) * $maxListPagination;
        $paginatedFiles = array_slice($filteredFiles, $offset, $maxListPagination);

        return [
            'items' => $paginatedFiles,
            'total' => count($filteredFiles),
            'page' => $page,
            'perPage' => $maxListPagination,
        ];
    }

    /**
     * @return array<int, array<string, int>>|int
     */
    public function findAllWithoutPartner(bool $count = false): array|int
    {
        $qb = $this->createQueryBuilder('f');
        if ($count) {
            $qb->select('COUNT(f.id)');
        } else {
            $qb->select('f.id, u.id AS user_id, t.id AS territory_id');
        }
        $qb
            ->innerJoin('f.uploadedBy', 'u')
            ->innerJoin('f.signalement', 'si')
            ->innerJoin('si.territory', 't')
            ->where('f.partner IS NULL')
            ->andWhere('JSON_CONTAINS(u.roles, :roleUsager) = 0')->setParameter('roleUsager', '"ROLE_USAGER"');
        if (!$count) {
            $qb->setMaxResults(50000);

            return $qb->getQuery()->getResult();
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<int, File>
     */
    public function findAllStandalone(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.isStandalone = true')
            ->andWhere('f.signalement IS NULL')
            ->getQuery()
            ->getResult();
    }
}
