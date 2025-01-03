<?php

namespace App\Specification\Affectation;

use App\Entity\Enum\PartnerType;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\Context\PartnerSignalementContext;
use App\Specification\Context\SpecificationContextInterface;
use App\Specification\SpecificationInterface;

class PartnerTypeSpecification implements SpecificationInterface
{
    public function __construct(private PartnerType $partnerType)
    {
        $this->partnerType = $partnerType;
    }

    public function isSatisfiedBy(SpecificationContextInterface $context): bool
    {
        if (!$context instanceof PartnerSignalementContext) {
            return false;
        }

        /** @var Partner $partner */
        $partner = $context->getPartner();

        /** @var Signalement $signalement */
        $signalement = $context->getSignalement();

        if ($partner->getType() !== $this->partnerType) {
            return false;
        }

        if (PartnerType::BAILLEUR_SOCIAL === $this->partnerType) {
            return $this->isSatisfiedByBailleurSocial($partner, $signalement);
        }

        return true;
    }

    private function isSatisfiedByBailleurSocial(Partner $partner, Signalement $signalement): bool
    {
        return null !== $partner->getBailleur()
            && null !== $signalement->getBailleur()
            && $partner->getBailleur() === $signalement->getBailleur();
    }
}
