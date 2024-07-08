<?php

namespace App\Specification\Affectation;

use App\Entity\Enum\PartnerType;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\SpecificationInterface;

class PartnerTypeSpecification implements SpecificationInterface
{
    private PartnerType $partnerType;

    public function __construct(PartnerType $partnerType)
    {
        $this->partnerType = $partnerType;
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

        return $partner->getType() === $this->partnerType;
    }
}
