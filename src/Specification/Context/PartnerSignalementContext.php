<?php

namespace App\Specification\Context;

use App\Entity\Partner;
use App\Entity\Signalement;

class PartnerSignalementContext implements SpecificationContextInterface
{
    public function __construct(private Partner $partner, private Signalement $signalement)
    {
    }

    public function getPartner(): Partner
    {
        return $this->partner;
    }

    public function getSignalement(): Signalement
    {
        return $this->signalement;
    }
}
