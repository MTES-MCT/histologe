<?php

namespace App\Manager;

use App\Entity\Signalement;
use Doctrine\Persistence\ManagerRegistry;

class SignalementManager extends AbstractManager
{
    public function __construct(ManagerRegistry $managerRegistry, string $entityName = Signalement::class)
    {
        parent::__construct($managerRegistry, $entityName);
    }
}
