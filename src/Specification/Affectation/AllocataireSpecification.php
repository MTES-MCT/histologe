<?php

namespace App\Specification\Affectation;

use App\Entity\Signalement;
use App\Specification\Context\PartnerSignalementContext;
use App\Specification\Context\SpecificationContextInterface;
use App\Specification\SpecificationInterface;

class AllocataireSpecification implements SpecificationInterface
{
    public function __construct(
        private string $ruleAllocataire
    ) {
    }

    public function isSatisfiedBy(SpecificationContextInterface $context): bool
    {
        if (!$context instanceof PartnerSignalementContext) {
            return false;
        }

        /** @var Signalement $signalement */
        $signalement = $context->getSignalement();

        $isAllocataire = strtolower($signalement->getIsAllocataire());
        switch ($this->ruleAllocataire) {
            case 'all':
                return true;
            case 'oui':
                return \in_array($isAllocataire, ['oui', 'caf', 'msa', '1']);
            case 'non':
                return \in_array($isAllocataire, ['non', '0']);
            default:
                return $isAllocataire === $this->ruleAllocataire;
        }

        return false;
    }
}
