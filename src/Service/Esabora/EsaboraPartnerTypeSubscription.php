<?php

namespace App\Service\Esabora;

use App\Entity\Enum\PartnerType;
use Twig\Extension\RuntimeExtensionInterface;

class EsaboraPartnerTypeSubscription implements RuntimeExtensionInterface
{
    public function isSubscribed(?PartnerType $partnerType): bool
    {
        return \in_array($partnerType, [PartnerType::ARS, PartnerType::COMMUNE_SCHS]);
    }
}
