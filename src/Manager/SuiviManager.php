<?php

namespace App\Manager;

use App\Entity\Suivi;
use App\Factory\SuiviFactory;
use Doctrine\Persistence\ManagerRegistry;

class SuiviManager extends Manager
{
    public function __construct(
        private SuiviFactory $suiviFactory,
        protected ManagerRegistry $managerRegistry,
        string $entityName = Suivi::class
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->entityName = $entityName;
    }

    public function createSuivi(array $params, bool $isPublic = false)
    {
        return $this->suiviFactory->createInstanceFrom($params, $isPublic);
    }
}
