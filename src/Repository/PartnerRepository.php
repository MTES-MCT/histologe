<?php

namespace App\Repository;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function getPartnersQueryBuilder(Territory|null $territory)
    {
        $queryBuilder = $this->createQueryBuilder('p')->where('p.isArchive != 1');

        if ($territory) {
            $queryBuilder->andWhere('p.territory = :territory')->setParameter('territory', $territory);
        }

        return $queryBuilder;
    }

    public function getPartners(Territory|null $territory, $page)
    {
        $maxResult = Partner::MAX_LIST_PAGINATION;
        $firstResult = ($page - 1) * $maxResult;

        $queryBuilder = $this->getPartnersQueryBuilder($territory);
        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        $paginator = new Paginator($queryBuilder->getQuery(), false);

        return $paginator;
    }

    /**
     * @deprecated
     */
    public function findAllOrByInseeIfCommune(string|null $insee, Territory|null $territory)
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.isArchive != 1');
        /*
         * @todo: necessary to clean data and add some constraint in partner creation to know
         * if partner should have `is_commune` 0 or 1 depends if code insee exists
         */
        if ($insee) {
            $qb->andWhere('p.isCommune = 0 OR p.isCommune = 1 AND p.insee LIKE :insee')
                ->setParameter('insee', '%'.$insee.'%');
        }
        if ($territory) {
            $qb->andWhere('p.territory = :territory')->setParameter('territory', $territory);
        }
        $qb
            ->leftJoin('p.affectations', 'affectations')
            ->addSelect('affectations');

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function findAllList(Territory|null $territory)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('PARTIAL p.{id,nom,isCommune}')
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
            ->select('PARTIAL p.{id,nom,isCommune}')
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
