<?php

namespace App\Repository;

use App\Entity\AutoAffectationRule;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Service\ListFilters\SearchAutoAffectationRule;
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

    /**
     * @return Paginator<AutoAffectationRule>
     */
    public function findFilteredPaginated(SearchAutoAffectationRule $searchAutoAffectationRule, int $maxResult): Paginator
    {
        return $this->getAutoAffectationRules(
            page: $searchAutoAffectationRule->getPage(),
            maxResult: $maxResult,
            territory: $searchAutoAffectationRule->getTerritory(),
            isActive: $searchAutoAffectationRule->getIsActive(),
        );
    }

    /**
     * @return Paginator<AutoAffectationRule>
     */
    public function getAutoAffectationRules(
        int $page,
        int $maxResult,
        ?Territory $territory,
        ?bool $isActive,
    ): Paginator {
        $queryBuilder = $this->createQueryBuilder('aar');

        if ($territory) {
            $queryBuilder->andWhere('aar.territory = :territory')->setParameter('territory', $territory);
        }
        if (null !== $isActive) {
            $queryBuilder->andWhere('aar.status = :status');
            $queryBuilder->setParameter('status', $isActive ? AutoAffectationRule::STATUS_ACTIVE : AutoAffectationRule::STATUS_ARCHIVED);
        }

        $firstResult = ($page - 1) * $maxResult;
        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($queryBuilder->getQuery(), false);
    }

    /**
     * @return array<int, AutoAffectationRule>
     */
    public function findForPartner(Partner $partner): array
    {
        $territory = $partner->getTerritory();
        $partnerType = $partner->getType();
        $status = AutoAffectationRule::STATUS_ACTIVE;
        $partnerToExclude = $partner->getId();

        return $this->createQueryBuilder('aar')
            ->andWhere('aar.territory = :territory')
            ->andWhere('aar.partnerType = :partnerType')
            ->andWhere('aar.status = :status')
            ->andWhere('JSON_CONTAINS(aar.partnerToExclude, :partnerToExclude) = 0')
            ->setParameter('territory', $territory)
            ->setParameter('partnerType', $partnerType)
            ->setParameter('status', $status)
            ->setParameter('partnerToExclude', json_encode((string) $partnerToExclude))
            ->getQuery()
            ->getResult();
    }
}
