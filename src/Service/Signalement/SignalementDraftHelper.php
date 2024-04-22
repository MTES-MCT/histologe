<?php

namespace App\Service\Signalement;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\ProfileDeclarant;

class SignalementDraftHelper
{
    public static function isTiersDeclarant(SignalementDraftRequest $signalementDraftRequest): bool
    {
        switch (strtoupper($signalementDraftRequest->getProfil())) {
            case ProfileDeclarant::SERVICE_SECOURS->name:
            case ProfileDeclarant::BAILLEUR->name:
            case ProfileDeclarant::TIERS_PRO->name:
            case ProfileDeclarant::TIERS_PARTICULIER->name:
                return true;
            case ProfileDeclarant::LOCATAIRE->name:
            case ProfileDeclarant::BAILLEUR_OCCUPANT->name:
            default:
                return false;
        }
    }

    public static function getEmailDeclarant(SignalementDraftRequest $signalementDraftRequest): ?string
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
