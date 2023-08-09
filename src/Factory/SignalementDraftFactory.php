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
            ->setCurrentStep('3:vos_coordonnees_occupant') /* @todo: https://github.com/MTES-MCT/histologe/issues/1597 */
            ->setProfileDeclarant(ProfileDeclarant::from(strtoupper($signalementDraftRequest->getProfil())));
    }

    public function getEmailDeclarent(SignalementDraftRequest $signalementDraftRequest): ?string
    {
        switch (strtoupper($signalementDraftRequest->getProfil())) {
            case ProfileDeclarant::SERVICE_SECOURS->name:
            case ProfileDeclarant::BAILLEUR->name:
                return $signalementDraftRequest->getVosCoordonneesTiersEmail();
            case ProfileDeclarant::LOCATAIRE->name:
            case ProfileDeclarant::BAILLEUR_OCCUPANT->name:
                return $signalementDraftRequest->getVosCoordonneesOccupantEmail();
            default:
                return null;
        }
    }
}
