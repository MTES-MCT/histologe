<?php

namespace App\Manager;

use App\Entity\Partner;
use Doctrine\Persistence\ManagerRegistry;

class PartnerManager extends AbstractManager
{
    public function __construct(ManagerRegistry $managerRegistry, string $entityName = Partner::class)
    {
        parent::__construct($managerRegistry, $entityName);
    }
}
