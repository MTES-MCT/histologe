<?php

namespace App\Manager;

use App\Entity\AutoAffectationRule;
use Doctrine\Persistence\ManagerRegistry;

class AutoAffectationRuleManager extends AbstractManager
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected string $entityName = AutoAffectationRule::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }
}
