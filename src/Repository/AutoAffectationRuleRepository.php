<?php

namespace App\Repository;

use App\Entity\AutoAffectationRule;
use App\Entity\Partner;
use App\Entity\Territory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AutoAffectationRule>
 */
class AutoAffectationRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AutoAffectationRule::class);
    }

    public function getAutoAffectationRules(
        Territory|null $territory,
        $page
    ): Paginator {
        $maxResult = Partner::MAX_LIST_PAGINATION;
        $firstResult = ($page - 1) * $maxResult;

        $queryBuilder = $this->createQueryBuilder('aar');

        if ($territory) {
            $queryBuilder->andWhere('aar.territory = :territory')->setParameter('territory', $territory);
        }

        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($queryBuilder->getQuery(), false);
    }
}
