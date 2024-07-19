<?php

namespace App\Factory;

use App\Entity\AutoAffectationRule;
use App\Entity\Enum\PartnerType;
use App\Entity\Territory;

class AutoAffectationRuleFactory
{
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
            ->setInseeToInclude($inseeToInclude)
            ->setParc($parc)
            ->setAllocataire($allocataire);

        if (!empty($inseeToExclude)) {
            $autoAffectationRule->setInseeToExclude(array_map('trim', explode(',', $inseeToExclude)));
        }

        return $autoAffectationRule;
    }
}
