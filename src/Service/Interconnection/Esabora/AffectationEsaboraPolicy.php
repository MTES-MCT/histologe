<?php

namespace App\Service\Interconnection\Esabora;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Repository\PartnerRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AffectationEsaboraPolicy
{
    public function __construct(
        private readonly PartnerRepository $partnerRepository,
        #[Autowire(env: 'FEATURE_SCHS_DISPATCH_SISH_ENABLE')]
        private readonly bool $featureSchsDispatchSishEnable,
    ) {
    }

    public function hasUrlConflict(array $partnerIds): bool
    {
        if (!$this->featureSchsDispatchSishEnable) {
            return false;
        }

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
        if (!$this->featureSchsDispatchSishEnable) {
            return true;
        }

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
