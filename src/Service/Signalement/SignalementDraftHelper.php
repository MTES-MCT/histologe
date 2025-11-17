<?php

namespace App\Service\Signalement;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\SignalementDraft;
use App\Serializer\SignalementDraftRequestSerializer;

class SignalementDraftHelper
{
    public function __construct(
        private readonly SignalementDraftRequestSerializer $signalementDraftRequestSerializer,
    ) {
    }

    public static function isTiersDeclarant(SignalementDraftRequest $signalementDraftRequest): bool
    {
        if (empty($signalementDraftRequest->getProfil())) {
            return false;
        }
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
        if (empty($signalementDraftRequest->getProfil())) {
            return null;
        }

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

    public function isPublicAndBailleurPrevenu(SignalementDraft $signalementDraft): bool
    {
        /** @var SignalementDraftRequest $signalementDraftRequest */
        $signalementDraftRequest = $this->signalementDraftRequestSerializer->denormalize(
            $signalementDraft->getPayload(),
            SignalementDraftRequest::class
        );

        $isTiersAndLogementSocial = false;
        switch (strtoupper($signalementDraftRequest->getProfil())) {
            case ProfileDeclarant::SERVICE_SECOURS->value:
                $isTiersAndLogementSocial = ('oui' === $signalementDraftRequest->getSignalementConcerneLogementSocialServiceSecours());
                break;
            case ProfileDeclarant::LOCATAIRE->value:
            case ProfileDeclarant::TIERS_PARTICULIER->value:
            case ProfileDeclarant::TIERS_PRO->value:
                $isTiersAndLogementSocial = ('oui' === $signalementDraftRequest->getSignalementConcerneLogementSocialAutreTiers());
                break;
        }

        if ($isTiersAndLogementSocial
            && 'oui' === $signalementDraftRequest->getInfoProcedureBailleurPrevenu()
        ) {
            return true;
        }

        return false;
    }

    public static function computeBailleurPrevenuAtFromRequest(
        SignalementDraftRequest $request,
    ): ?\DateTimeImmutable {
        if (null === $request->getInfoProcedureBailDate()) {
            return null;
        }

        if ('oui' !== $request->getInfoProcedureBailleurPrevenu()) {
            return null;
        }

        return self::computePrevenuBailleurAt($request->getInfoProcedureBailDate());
    }

    public static function computePrevenuBailleurAt(string $infoProcedureBailDate): ?\DateTimeImmutable
    {
        // le ! force l’heure/date à 00:00:00 et le jour au premier jour du mois
        $dateBailleurPrevenu = \DateTimeImmutable::createFromFormat('!m/Y', $infoProcedureBailDate);
        if ($dateBailleurPrevenu instanceof \DateTimeImmutable) {
            return $dateBailleurPrevenu;
        }

        return null;
    }
}
