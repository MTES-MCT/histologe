<?php

namespace App\Factory;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\SignalementDraft;

class SignalementDraftFactory
{
    public function createInstanceFrom(
        SignalementDraftRequest $signalementDraftRequest,
        array $payload
    ): SignalementDraft {
        return (new SignalementDraft())
            ->setPayload($payload)
            ->setAddressComplete($signalementDraftRequest->getAdresseLogementAdresse())
            ->setEmailDeclarant($signalementDraftRequest->getVosCoordonneesOccupantEmail())
            ->setCurrentStep('3:vos_coordonnees_occupant')
            ->setProfileDeclarant(ProfileDeclarant::LOCATAIRE);
    }
}
