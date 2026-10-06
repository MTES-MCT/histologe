<?php

namespace App\Specification\Affectation;

use App\Specification\Context\PartnerSignalementContext;
use App\Specification\Context\SpecificationContextInterface;
use App\Specification\SpecificationInterface;

readonly class ZoneSpecification implements SpecificationInterface
{
    /**
     * @param ?array<string> $zoneToInclude      ids des zones incluses de la règle
     * @param ?array<string> $zoneToExclude      ids des zones exclues de la règle
     * @param array<int>     $signalementZoneIds ids des zones contenant la géolocalisation du signalement
     */
    public function __construct(
        private ?array $zoneToInclude,
        private ?array $zoneToExclude,
        private array $signalementZoneIds,
    ) {
    }

    public function isSatisfiedBy(SpecificationContextInterface $context): bool
    {
        if (!$context instanceof PartnerSignalementContext) {
            return false;
        }

        if (!empty($this->zoneToInclude) && empty(array_intersect(array_map('intval', $this->zoneToInclude), $this->signalementZoneIds))) {
            return false;
        }

        if (!empty($this->zoneToExclude) && !empty(array_intersect(array_map('intval', $this->zoneToExclude), $this->signalementZoneIds))) {
            return false;
        }

        return true;
    }
}
