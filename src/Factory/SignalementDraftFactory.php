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
            ->setEmailDeclarant($this->getEmailDeclarent($signalementDraftRequest))
            ->setCurrentStep($signalementDraftRequest->getCurrentStep())
            ->setProfileDeclarant(ProfileDeclarant::from(strtoupper($signalementDraftRequest->getProfil())));
    }

    public function getEmailDeclarent(SignalementDraftRequest $signalementDraftRequest): ?string
    {
        switch (strtoupper($signalementDraftRequest->getProfil())) {
            case ProfileDeclarant::SERVICE_SECOURS->name:
            case ProfileDeclarant::BAILLEUR->name:
            case ProfileDeclarant::TIERS_PRO->name:
            case ProfileDeclarant::TIERS_PARTICULIER->name:
                return $signalementDraftRequest->getVosCoordonneesTiersEmail();
            case ProfileDeclarant::LOCATAIRE->name:
            case ProfileDeclarant::BAILLEUR_OCCUPANT->name:
                return $signalementDraftRequest->getVosCoordonneesOccupantEmail();
            default:
                return null;
        }
    }
}
