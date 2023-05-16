<?php

namespace App\Manager;

use App\Entity\Intervention;
use Doctrine\Persistence\ManagerRegistry;

class InterventionManager extends AbstractManager
{
    public function __construct(ManagerRegistry $managerRegistry, string $entityName = Intervention::class)
    {
        parent::__construct($managerRegistry, $entityName);
    }
}
