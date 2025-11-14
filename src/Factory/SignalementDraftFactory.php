<?php

namespace App\Factory;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\SignalementDraft;
use App\Service\Signalement\SignalementDraftHelper;

class SignalementDraftFactory
{
    /**
     * @param array<string, mixed> $payload
     */
    public function createInstanceFrom(
        SignalementDraftRequest $signalementDraftRequest,
        array $payload,
    ): SignalementDraft {
        $signalementDraft = new SignalementDraft();

        if (null !== $signalementDraftRequest->getInfoProcedureBailDate()
            && 'oui' === $signalementDraftRequest->getInfoProcedureBailleurPrevenu()
        ) {
            $infoProcedureBailDate = SignalementDraftHelper::computePrevenuBailleurAt(
                $signalementDraftRequest->getInfoProcedureBailDate()
            );
            $signalementDraft->setBailleurPrevenuAt($infoProcedureBailDate);
        }

        return $signalementDraft
            ->setPayload($payload)
            ->setAddressComplete($signalementDraftRequest->getAdresseLogementAdresse())
            ->setEmailDeclarant(SignalementDraftHelper::getEmailDeclarant($signalementDraftRequest))
            ->setCurrentStep($signalementDraftRequest->getCurrentStep())
            ->setProfileDeclarant(ProfileDeclarant::from(strtoupper($signalementDraftRequest->getProfil())));
    }
}
