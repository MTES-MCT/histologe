<?php

namespace App\Specification\Affectation;

use App\Specification\Context\PartnerSignalementContext;
use App\Specification\Context\SpecificationContextInterface;
use App\Specification\SpecificationInterface;

readonly class ZoneExcludeSpecification implements SpecificationInterface
{
    /**
     * @param ?array<string> $zoneToExclude      ids des zones exclues de la règle
     * @param array<int>     $signalementZoneIds ids des zones contenant la géolocalisation du signalement
     */
    public function __construct(
        private ?array $zoneToExclude,
        private array $signalementZoneIds,
    ) {
    }

    public function isSatisfiedBy(SpecificationContextInterface $context): bool
    {
        if (!$context instanceof PartnerSignalementContext) {
            return false;
        }

        if (empty($this->zoneToExclude)) {
            return true;
        }

        return empty(array_intersect(array_map('intval', $this->zoneToExclude), $this->signalementZoneIds));
    }
}
