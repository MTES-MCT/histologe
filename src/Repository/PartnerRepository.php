<?php

namespace App\Repository;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
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

    public function getPartners(
        Territory|null $territory,
        PartnerType|null $type,
        ?string $filterTerms,
        $page
    ): Paginator {
        $maxResult = Partner::MAX_LIST_PAGINATION;
        $firstResult = ($page - 1) * $maxResult;

        $queryBuilder = $this->getPartnersQueryBuilder($territory);

        if (!empty($type)) {
            $queryBuilder
                ->andWhere('p.type = :type')
                ->setParameter('type', $type);
        }

        if (!empty($filterTerms)) {
            $queryBuilder
                ->andWhere('LOWER(p.nom) LIKE :usersterms
                OR LOWER(p.email) LIKE :usersterms');
            $queryBuilder
                ->setParameter('usersterms', '%'.strtolower($filterTerms).'%');
        }

        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        $paginator = new Paginator($queryBuilder->getQuery(), false);

        return $paginator;
    }

    /**
     * @throws QueryException
     */
    public function findAllList(Territory|null $territory = null)
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
    public function findByLocalization(Signalement $signalement, bool $affected = true, bool $addCompetences = false): array
    {
        $operator = $affected ? 'IN' : 'NOT IN';

        $subquery = $this->getEntityManager()->getRepository(Affectation::class)->createQueryBuilder('a')
        ->select('IDENTITY(a.partner)')
        ->where('a.signalement = :signalement')
        ->setParameter('signalement', $signalement);

        $affectedPartners = $subquery->getQuery()->getSingleColumnResult();

        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder->select('p.id, p.nom as name')
            ->where('p.isArchive = 0')
            ->andWhere('p.territory = :territory')
            ->setParameter('territory', $signalement->getTerritory())
            ->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('p.isCommune', 0),
                    $queryBuilder->expr()->like('p.insee', ':insee')
                )
            )
            ->setParameter('insee', '%'.$signalement->getInseeOccupant().'%')
        ;
        if (\count($affectedPartners) > 0 || 'IN' == $operator) {
            $queryBuilder->andWhere('p.id '.$operator.' (:subquery)')
            ->setParameter('subquery', $affectedPartners);
        }
        if ($addCompetences) {
            $queryBuilder->addSelect('p.competence');
            $queryBuilder->orderBy('p.competence', 'DESC');
        }

        return $queryBuilder->getQuery()->getArrayResult();
    }
}
