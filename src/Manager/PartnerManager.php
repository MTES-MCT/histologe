<?php

namespace App\Manager;

use App\Entity\Partner;
use Doctrine\Persistence\ManagerRegistry;

class PartnerManager extends AbstractManager
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected string $entityName = Partner::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }
}
