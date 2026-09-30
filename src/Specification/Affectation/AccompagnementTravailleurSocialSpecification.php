<?php

namespace App\Specification\Affectation;

use App\Entity\Signalement;
use App\Specification\Context\PartnerSignalementContext;
use App\Specification\Context\SpecificationContextInterface;
use App\Specification\SpecificationInterface;

readonly class AccompagnementTravailleurSocialSpecification implements SpecificationInterface
{
    public function __construct(
        private string $ruleAccompagnementTravailleurSocial,
    ) {
    }

    public function isSatisfiedBy(SpecificationContextInterface $context): bool
    {
        if (!$context instanceof PartnerSignalementContext) {
            return false;
        }

        /** @var Signalement $signalement */
        $signalement = $context->getSignalement();

        $accompagnementTravailleurSocial = strtolower((string) $signalement->getSituationFoyer()?->getTravailleurSocialAccompagnement());
        switch ($this->ruleAccompagnementTravailleurSocial) {
            case 'all':
                return true;
            case 'nsp':
                return \in_array($accompagnementTravailleurSocial, ['', 'nsp']);
            default:
                return $accompagnementTravailleurSocial === $this->ruleAccompagnementTravailleurSocial;
        }
    }
}
