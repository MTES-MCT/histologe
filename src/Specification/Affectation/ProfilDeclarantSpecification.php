<?php

namespace App\Specification\Affectation;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Signalement;
use App\Specification\Context\PartnerSignalementContext;
use App\Specification\Context\SpecificationContextInterface;
use App\Specification\SpecificationInterface;

class ProfilDeclarantSpecification implements SpecificationInterface
{
    public function __construct(private string $ruleProfilDeclarant)
    {
        $this->ruleProfilDeclarant = $ruleProfilDeclarant;
    }

    public function isSatisfiedBy(SpecificationContextInterface $context): bool
    {
        if (!$context instanceof PartnerSignalementContext) {
            return false;
        }

        /** @var Signalement $signalement */
        $signalement = $context->getSignalement();

        /** @var ProfileDeclarant $signalementProfilDeclarant */
        $signalementProfilDeclarant = $signalement->getProfileDeclarant();

        switch ($this->ruleProfilDeclarant) {
            case 'all':
                return true;
            case 'tiers':
                if ($signalement->isTiersDeclarant()) {
                    return true;
                }

                return false;
            case 'occupant':
                if (!$signalement->isTiersDeclarant()) {
                    return true;
                }

                return false;
            default:
                return $signalementProfilDeclarant->value === $this->ruleProfilDeclarant;
        }
    }
}
