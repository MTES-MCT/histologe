<?php

namespace App\Service\Signalement\Suivi;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Partner;
use App\Entity\Suivi;
use App\Repository\PartnerRepository;

readonly class SuiviMentionExtractor
{
    public function __construct(
        private readonly PartnerRepository $partnerRepository,
    ) {
    }

    /**
     * @return array<int, Partner>
     */
    public function extract(Suivi $suivi): array
    {
        if ($suivi->getIsVisibleForUsager() || $suivi->getIsVisibleForBailleur() || SuiviCategory::MESSAGE_PARTNER !== $suivi->getCategory()) {
            return []; // pas de notification de mention si le suivi est visible usager/bailleur
        }

        preg_match_all('/data-partner-id="(\d+)"/', $suivi->getDescription(raw: true), $matches);

        $mentionedPartnersId = array_unique(array_map('intval', $matches[1]));
        $mentionedPartners = [];

        foreach ($mentionedPartnersId as $partnerId) {
            $partner = $this->partnerRepository->find($partnerId);
            if (!$partner instanceof Partner) {
                continue;
            }
            $affectation = $suivi->getSignalement()->getAffectationForPartner($partner);
            if ($affectation instanceof Affectation && AffectationStatus::ACCEPTED === $affectation->getStatut()) {
                $mentionedPartners[] = $partner;
            }
        }

        return $mentionedPartners;
    }
}
