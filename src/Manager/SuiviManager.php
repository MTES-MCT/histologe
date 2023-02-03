<?php

namespace App\Manager;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Factory\SuiviFactory;
use Doctrine\Persistence\ManagerRegistry;

class SuiviManager extends Manager
{
    public function __construct(
        private SuiviFactory $suiviFactory,
        protected ManagerRegistry $managerRegistry,
        string $entityName = Suivi::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createSuivi(User $user, Signalement $signalement, array $params, bool $isPublic = false): Suivi
    {
        return $this->suiviFactory->createInstanceFrom($user, $signalement, $params, $isPublic);
    }

    public function updateSuiviCreatedByUser(Suivi $suivi, User $user): Suivi
    {
        $suivi->setCreatedBy($user);

        $this->save($suivi);

        return $suivi;
    }
}
