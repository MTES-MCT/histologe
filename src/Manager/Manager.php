<?php

namespace App\Manager;

use Doctrine\Persistence\ManagerRegistry;

class Manager
{
    public function __construct(
        protected ManagerRegistry $managerRegistry,
        protected string $entityName = '',
    ) {
    }
}
