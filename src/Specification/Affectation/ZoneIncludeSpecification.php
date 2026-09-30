<?php

namespace App\Specification\Affectation;

use App\Specification\Context\PartnerSignalementContext;
use App\Specification\Context\SpecificationContextInterface;
use App\Specification\SpecificationInterface;

readonly class ZoneIncludeSpecification implements SpecificationInterface
{
    /**
     * @param ?array<string> $zoneToInclude      ids des zones de la règle
     * @param array<int>     $signalementZoneIds ids des zones contenant la géolocalisation du signalement
     */
    public function __construct(
        private ?array $zoneToInclude,
        private array $signalementZoneIds,
    ) {
    }

    public function isSatisfiedBy(SpecificationContextInterface $context): bool
    {
        if (!$context instanceof PartnerSignalementContext) {
            return false;
        }

        if (empty($this->zoneToInclude)) {
            return true;
        }

        return !empty(array_intersect(array_map('intval', $this->zoneToInclude), $this->signalementZoneIds));
    }
}
