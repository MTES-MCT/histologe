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

        $result = false;

        switch ($this->ruleParc) {
            case 'all':
                $result = true;
                break;
            case 'public':
                $result = true === $signalement->getIsLogementSocial();
                break;
            case 'prive':
                $result = false === $signalement->getIsLogementSocial();
                break;
            case 'non_renseigne':
                $result = null === $signalement->getIsLogementSocial();
                break;
        }

        return $result;
    }
}
