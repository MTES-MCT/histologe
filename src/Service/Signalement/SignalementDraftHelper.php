<?php

namespace App\Service\Signalement;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\SignalementDraft;
use App\Serializer\SignalementDraftRequestSerializer;
use Symfony\Component\Clock\ClockInterface;

class SignalementDraftHelper
{
    private const NB_DAYS_DURATION_BAILLEUR_PREVENU = 90;

    public function __construct(
        private readonly SignalementDraftRequestSerializer $signalementDraftRequestSerializer,
        private readonly ClockInterface $clock,
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

    public function isPublicAndBailleurPrevenuPeriodPassed(SignalementDraft $signalementDraft): bool
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
            && !empty($signalementDraftRequest->getInfoProcedureBailDate())
        ) {
            /** @var \DateTimeImmutable $dateBailleurPrevenu */
            $dateBailleurPrevenu = \DateTimeImmutable::createFromFormat('d/m/Y', '01/'.$signalementDraftRequest->getInfoProcedureBailDate());
            $dateToday = $this->clock->now();
            $durationSincePrevenu = $dateToday->diff($dateBailleurPrevenu);
            if ($durationSincePrevenu->days > self::NB_DAYS_DURATION_BAILLEUR_PREVENU) {
                return true;
            }
        }

        return false;
    }
}
