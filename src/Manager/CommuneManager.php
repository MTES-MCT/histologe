<?php

namespace App\Manager;

use App\Entity\Commune;
use Doctrine\Persistence\ManagerRegistry;

class CommuneManager extends AbstractManager
{
    public function __construct(ManagerRegistry $managerRegistry, string $entityName = Commune::class)
    {
        parent::__construct($managerRegistry, $entityName);
    }
}
