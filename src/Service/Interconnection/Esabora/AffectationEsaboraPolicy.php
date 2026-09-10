<?php

namespace App\Service\Interconnection\Esabora;

use App\Entity\Partner;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AffectationEsaboraPolicy
{
    public function __construct(
        #[Autowire(env: 'FEATURE_SCHS_DISPATCH_SISH_ENABLE')]
        private readonly bool $featureSchsDispatchSishEnable,
    ) {
    }

    /**
     * @param array<int, Partner> $partners
     */
    public function hasUrlConflict(array $partners): bool
    {
        if (!$this->featureSchsDispatchSishEnable) {
            return false;
        }

        if ([] === $partners) {
            return false;
        }
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
}
