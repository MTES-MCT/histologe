<?php

namespace App\Manager;

use App\Entity\Territory;
use Doctrine\Persistence\ManagerRegistry;

class TerritoryManager extends AbstractManager
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected string $entityName = Territory::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }
}
