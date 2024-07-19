<?php

namespace App\Specification\Affectation;

use App\Entity\Enum\PartnerType;
use App\Entity\Partner;
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

        return $partner->getType() === $this->partnerType;
    }
}
