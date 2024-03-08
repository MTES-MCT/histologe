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
        string $entityName = Suivi::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createSuivi(
        ?User $user,
        Signalement $signalement,
        array $params,
        bool $isPublic = false,
        bool $flush = false,
        string $context = ''
    ): Suivi {
        $suivi = $this->suiviFactory->createInstanceFrom($user, $signalement, $params, $isPublic, $context);

        if ($flush) {
            $this->save($suivi);
        }

        return $suivi;
    }

    public function updateSuiviCreatedByUser(Suivi $suivi, User $user): Suivi
    {
        $suivi->setCreatedBy($user);

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
                params: [
                    'type' => Suivi::TYPE_AUTO,
                    'description' => $description.$user->getNomComplet(),
                ],
                flush: true
            );
        }
    }
}
