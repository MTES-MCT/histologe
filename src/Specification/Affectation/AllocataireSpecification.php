<?php

namespace App\Specification\Affectation;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\SpecificationInterface;

class AllocataireSpecification implements SpecificationInterface
{
    private string $ruleAllocataire;

    public function __construct(string $ruleAllocataire)
    {
        $this->ruleAllocataire = $ruleAllocataire;
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

        if ('all' === $this->ruleAllocataire) {
            return true;
        } elseif ('oui' === $this->ruleAllocataire) {
            if ('oui' === $signalement->getIsAllocataire()
            || 'CAF' === $signalement->getIsAllocataire()
            || 'MSA' === $signalement->getIsAllocataire()
            || '1' === $signalement->getIsAllocataire()
            ) {
                return true;
            }
        } elseif ('non' === $this->ruleAllocataire) {
            if ('non' === $signalement->getIsAllocataire()
            || '0' === $signalement->getIsAllocataire()
            ) {
                return true;
            }
        } else {
            return strtolower($signalement->getIsAllocataire()) === $this->ruleAllocataire;
        }

        return false;
    }
}
