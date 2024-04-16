<?php

namespace App\Factory;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\SignalementDraft;
use App\Service\Signalement\SignalementDraftHelper;

class SignalementDraftFactory
{
    public function createInstanceFrom(
        SignalementDraftRequest $signalementDraftRequest,
        array $payload
    ): SignalementDraft {
        return (new SignalementDraft())
            ->setPayload($payload)
            ->setAddressComplete($signalementDraftRequest->getAdresseLogementAdresse())
            ->setEmailDeclarant(SignalementDraftHelper::getEmailDeclarant($signalementDraftRequest))
            ->setCurrentStep($signalementDraftRequest->getCurrentStep())
            ->setProfileDeclarant(ProfileDeclarant::from(strtoupper($signalementDraftRequest->getProfil())));
    }
}
