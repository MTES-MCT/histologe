<?php

namespace App\Specification\Affectation;

use App\Entity\Partner;
use App\Specification\Context\PartnerSignalementContext;
use App\Specification\Context\SpecificationContextInterface;
use App\Specification\SpecificationInterface;

class PartnerExcludeSpecification implements SpecificationInterface
{
    /**
     * @param ?array<int> $partnerToExclude
     */
    public function __construct(private ?array $partnerToExclude)
    {
        $this->partnerToExclude = $partnerToExclude;
    }

    public function isSatisfiedBy(SpecificationContextInterface $context): bool
    {
        if (!$context instanceof PartnerSignalementContext) {
            return false;
        }

        /** @var Partner $partner */
        $partner = $context->getPartner();

        if (!empty($this->partnerToExclude) && \in_array($partner->getId(), $this->partnerToExclude)) {
            return false;
        }

        return true;
    }
}
