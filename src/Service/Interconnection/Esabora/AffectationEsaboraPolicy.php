<?php

namespace App\Service\Interconnection\Esabora;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Repository\PartnerRepository;

class AffectationEsaboraPolicy
{
    public function __construct(
        private readonly PartnerRepository $partnerRepository,
    ) {
    }

    public function hasUrlConflict(array $partnerIds): bool
    {
        if ([] === $partnerIds) {
            return false;
        }

        $partners = $this->partnerRepository->findByIds($partnerIds);
        $countByUrl = [];

        foreach ($partners as $partner) {
            if (!$partner->canSyncWithEsabora()) {
                continue;
            }

            $url = $partner->getEsaboraUrl();
            if (!$url) {
                continue;
            }

            $countByUrl[$url] = ($countByUrl[$url] ?? 0) + 1;

            if ($countByUrl[$url] > 1) {
                return true;
            }
        }

        return false;
    }

    public function canBeAffected(Signalement $signalement, Partner $partner): bool
    {
        if (!$partner->isConnectedToSanteHabitat()) {
            return true;
        }

        $affectation = $signalement->getRelatedAffectationsConnectedToSish();

        if (null == $affectation) {
            return true;
        }

        return false;
    }
}
