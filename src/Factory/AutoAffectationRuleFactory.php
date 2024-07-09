<?php

namespace App\Factory;

use App\Entity\AutoAffectationRule;
use App\Entity\Enum\PartnerType;
use App\Entity\Territory;

class AutoAffectationRuleFactory
{
    public function __construct(
    ) {
    }

    public function createInstanceFrom(
        Territory $territory,
        string $status = null,
        PartnerType $partnerType = null,
        string $profileDeclarant = null,
        string $inseeToInclude = null,
        ?string $inseeToExclude = null,
        string $parc = null,
        string $allocataire = null
    ): AutoAffectationRule {
        $autoAffectationRule = (new AutoAffectationRule())
            ->setTerritory($territory)
            ->setStatus($status)
            ->setPartnerType($partnerType)
            ->setProfileDeclarant($profileDeclarant)
            ->setParc($parc)
            ->setAllocataire($allocataire);

        if (!empty($inseeToInclude)) {
            // $autoAffectationRule->setInseeToInclude(array_map('trim', explode(',', $inseeToInclude)));
            $autoAffectationRule->setInseeToInclude($inseeToInclude);
        }
        if (!empty($inseeToExclude)) {
            $autoAffectationRule->setInseeToExclude(array_map('trim', explode(',', $inseeToExclude)));
        }

        return $autoAffectationRule;
    }
}
