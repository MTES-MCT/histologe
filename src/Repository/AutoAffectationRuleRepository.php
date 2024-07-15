<?php

namespace App\Repository;

use App\Entity\AutoAffectationRule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}
