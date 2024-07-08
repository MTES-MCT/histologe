<?php

namespace App\Specification\Affectation;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\SpecificationInterface;

class ParcSpecification implements SpecificationInterface
{
    private string $ruleParc;

    public function __construct(string $ruleParc)
    {
        $this->ruleParc = $ruleParc;
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
        if ('all' === $this->ruleParc) {
            return true;
        } elseif ('public' === $this->ruleParc) {
            if ($signalement->getIsLogementSocial()
            ) {
                return true;
            }
        } elseif ('prive' === $this->ruleParc) {
            if (!$signalement->getIsLogementSocial()
            ) {
                return true;
            }
        }

        return false;
    }
}
