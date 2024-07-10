<?php

namespace App\Specification\Affectation;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\SpecificationInterface;

class ProfilDeclarantSpecification implements SpecificationInterface
{
    private string $ruleProfilDeclarant;

    public function __construct(string $ruleProfilDeclarant)
    {
        $this->ruleProfilDeclarant = $ruleProfilDeclarant;
    }

    public function isSatisfiedBy(array $params): bool
    {
        if (!isset($params['partner']) || !$params['partner'] instanceof Partner) {
            return false;
        }

        if (!isset($params['signalement']) || !$params['signalement'] instanceof Signalement) {
            return false;
        }
        /** @var Partner $partner */
        $partner = $params['partner'];

        /** @var Signalement $signalement */
        $signalement = $params['signalement'];

        /** @var ProfileDeclarant $signalementProfilDeclarant */
        $signalementProfilDeclarant = $signalement->getProfileDeclarant();

        if ('all' === $this->ruleProfilDeclarant) {
            return true;
        } elseif ('tiers' === $this->ruleProfilDeclarant) {
            if ($signalement->isTiersDeclarant()) {
                return true;
            }

            return false;
        } elseif ('occupant' === $this->ruleProfilDeclarant) {
            if (!$signalement->isTiersDeclarant()) {
                return true;
            }

            return false;
        }

        return $signalementProfilDeclarant->value === $this->ruleProfilDeclarant;
    }
}
