<?php

namespace App\Repository;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Partner|null find($id, $lockMode = null, $lockVersion = null)
 * @method Partner|null findOneBy(array $criteria, array $orderBy = null)
 * @method Partner[]    findAll()
 * @method Partner[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PartnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Partner::class);
    }

    public function getPartnersQueryBuilder(Territory|null $territory): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('p')->where('p.isArchive != 1');

        if ($territory) {
            $queryBuilder->andWhere('p.territory = :territory')->setParameter('territory', $territory);
        }

        return $queryBuilder;
    }

    public function getPartners(Territory|null $territory, $page): Paginator
    {
        $maxResult = Partner::MAX_LIST_PAGINATION;
        $firstResult = ($page - 1) * $maxResult;

        $queryBuilder = $this->getPartnersQueryBuilder($territory);
        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        $paginator = new Paginator($queryBuilder->getQuery(), false);

        return $paginator;
    }

    /**
     * @throws QueryException
     */
    public function findAllList(Territory|null $territory)
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.isArchive != 1');
        if ($territory) {
            $qb->andWhere('p.territory = :territory')->setParameter('territory', $territory);
        }

        return $qb->indexBy('p', 'p.id')
            ->getQuery()
            ->getResult();
    }

    public function findAllWithoutTerritory()
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.isArchive != 1')
            ->andWhere('p.territory IS NULL');

        return $qb->indexBy('p', 'p.id')
            ->getQuery()
            ->getResult();
    }

    public function findWithCodeInseeNotNull()
    {
        return $this
            ->createQueryBuilder('p')
            ->where("p.insee NOT LIKE '[\"\"]'")
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws Exception
     */
    public function findByLocalization(Signalement $signalement, bool $affected = true): array
    {
        $connection = $this->getEntityManager()->getConnection();
        $operator = $affected ? 'IN' : 'NOT IN';

        $sql = '
        SELECT id, nom as name
        FROM partner
        WHERE territory_id = :territory_id AND is_archive = 0 AND (is_commune = 0 OR insee LIKE :insee)
        AND id '.$operator.' (
            SELECT partner_id FROM affectation WHERE signalement_id = :signalement_id)
        ';

        $statement = $connection->prepare($sql);

        return $statement->executeQuery([
            'signalement_id' => $signalement->getId(),
            'territory_id' => $signalement->getTerritory()->getId(),
            'insee' => '%'.$signalement->getInseeOccupant().'%',
        ])->fetchAllAssociative();
    }
}
