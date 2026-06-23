<?php

namespace App\Service\Signalement;

use App\Entity\Signalement;
use App\Factory\SuiviDelayedFactory;
use App\Security\User\SignalementUser;
use Doctrine\ORM\EntityManagerInterface;

class SignalementUpdateService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SuiviDelayedFactory $suiviDelayedFactory,
    ) {
    }

    public function saveChangesAndCreateSuivi(Signalement $signalement, SignalementUser $signalementUser): void
    {
        // Ordre volontaire : createSuiviDelayedFromSignalementChanges() utilise les changements enregistrés sur Signalement en preUpdate.
        $this->entityManager->wrapInTransaction(function () use ($signalement, $signalementUser): void {
            $this->entityManager->flush(); /* @see SignalementUpdatedListener::preUpdate() écoute l'event dispatché par le flush() */
            $suiviDelayed = $this->suiviDelayedFactory->createSuiviDelayedFromSignalementChanges($signalementUser->getUser(), $signalement);
            $this->entityManager->persist($suiviDelayed);
            $this->entityManager->flush();
        });
    }

    // on pourrait déplacer le travail du SignalementUpdatedListener dans cette nouvelle classe
    // cela permettrait de garder le flush coté controller
}
