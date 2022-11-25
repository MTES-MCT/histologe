<?php

namespace App\Manager;

use App\Entity\Suivi;
use App\Factory\SuiviFactory;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

class SuiviManager extends Manager
{
    public function __construct(
        private SuiviFactory $suiviFactory,
        private Security $security,
        protected ManagerRegistry $managerRegistry,
        string $entityName = Suivi::class
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->entityName = $entityName;
    }

    public function createSuivi(string $description)
    {
        $suivi = $this->suiviFactory->createInstance();
        $suivi->setDescription($description);

        return $suivi;
    }
}
