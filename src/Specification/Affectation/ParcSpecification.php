<?php

namespace App\Specification\Affectation;

use App\Entity\Signalement;
use App\Specification\Context\PartnerSignalementContext;
use App\Specification\Context\SpecificationContextInterface;
use App\Specification\SpecificationInterface;

class ParcSpecification implements SpecificationInterface
{
    public function __construct(private string $ruleParc)
    {
        $this->ruleParc = $ruleParc;
    }

    public function isSatisfiedBy(SpecificationContextInterface $context): bool
    {
        if (!$context instanceof PartnerSignalementContext) {
            return false;
        }

        /** @var Signalement $signalement */
        $signalement = $context->getSignalement();

        switch ($this->ruleParc) {
            case 'all':
                return true;
            case 'public':
                if ($signalement->getIsLogementSocial()) {
                    return true;
                }
                break;
            case 'prive':
                if (false === $signalement->getIsLogementSocial()
                ) {
                    return true;
                }
                break;
            case 'non_renseigne':
                if (null === $signalement->getIsLogementSocial()
                ) {
                    return true;
                }
                break;
        }

        return false;
    }
}
