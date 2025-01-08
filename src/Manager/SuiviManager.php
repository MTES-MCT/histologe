<?php

namespace App\Manager;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\EventListener\SignalementUpdatedListener;
use App\Factory\SuiviFactory;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

class SuiviManager extends Manager
{
    public function __construct(
        private readonly SuiviFactory $suiviFactory,
        protected ManagerRegistry $managerRegistry,
        protected SignalementUpdatedListener $signalementUpdatedListener,
        protected Security $security,
        string $entityName = Suivi::class,
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createSuivi(
        Signalement $signalement,
        string $description,
        int $type,
        bool $isPublic = false,
        ?User $user = null,
        ?string $context = null,
    ): Suivi {
        $suivi = $this->suiviFactory->createInstanceFrom($signalement, $description, $type, $isPublic, $user, $context);
        $this->save($suivi);

        return $suivi;
    }

    public function addSuiviIfNeeded(
        Signalement $signalement,
        string $description,
    ): void {
        if ($this->signalementUpdatedListener->updateOccurred()) {
            /** @var User $user */
            $user = $this->security->getUser();
            $this->createSuivi(
                user: $user,
                signalement: $signalement,
                description: $description.$user->getNomComplet(),
                type: Suivi::TYPE_AUTO,
            );
        }
    }
}
