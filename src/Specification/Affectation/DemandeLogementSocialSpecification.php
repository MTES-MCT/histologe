<?php

namespace App\Specification\Affectation;

use App\Entity\Signalement;
use App\Specification\Context\PartnerSignalementContext;
use App\Specification\Context\SpecificationContextInterface;
use App\Specification\SpecificationInterface;

readonly class DemandeLogementSocialSpecification implements SpecificationInterface
{
    public function __construct(
        private string $ruleDemandeLogementSocial,
    ) {
    }

    public function isSatisfiedBy(SpecificationContextInterface $context): bool
    {
        if (!$context instanceof PartnerSignalementContext) {
            return false;
        }

        /** @var Signalement $signalement */
        $signalement = $context->getSignalement();

        $demandeLogementSocial = strtolower((string) $signalement->getSituationFoyer()?->getLogementSocialDemandeRelogement());
        switch ($this->ruleDemandeLogementSocial) {
            case 'all':
                return true;
            case 'nsp':
                return \in_array($demandeLogementSocial, ['', 'nsp']);
            default:
                return $demandeLogementSocial === $this->ruleDemandeLogementSocial;
        }
    }
}
