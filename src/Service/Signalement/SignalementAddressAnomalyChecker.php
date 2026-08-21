<?php

namespace App\Service\Signalement;

use App\Entity\Enum\SignalementAddressAnomaly;
use App\Entity\Signalement;
use App\Entity\Territory;

class SignalementAddressAnomalyChecker
{
    public function __construct(
        private readonly ZipcodeProvider $zipcodeProvider,
    ) {
    }

    public function getCalculatedTerritory(Signalement $signalement): ?Territory
    {
        $inseeOccupant = trim((string) $signalement->getInseeOccupant());
        $cpOccupant = trim((string) $signalement->getCpOccupant());

        return $this->zipcodeProvider->getTerritoryByInseeCode($inseeOccupant)
            ?? $this->zipcodeProvider->getTerritoryByPostalCode($cpOccupant);
    }

    /**
     * @return array<SignalementAddressAnomaly>
     */
    public function getErrors(Signalement $signalement): array
    {
        $inseeOccupant = trim((string) $signalement->getInseeOccupant());
        $cpOccupant = trim((string) $signalement->getCpOccupant());

        $inseeMissing = !$inseeOccupant || '#N/D' === $inseeOccupant;
        $cpMissing = !$cpOccupant || '#N/D' === $cpOccupant;
        if ($inseeMissing && $cpMissing) {
            return [SignalementAddressAnomaly::MISSING_CP_AND_INSEE];
        }

        $errors = [];

        if ($inseeOccupant && 5 !== strlen($inseeOccupant)) {
            $errors[] = SignalementAddressAnomaly::INVALID_INSEE_FORMAT;
        }

        $calculatedTerritory = $this->getCalculatedTerritory($signalement);
        if ($calculatedTerritory && $signalement->getTerritory() && $calculatedTerritory->getId() !== $signalement->getTerritory()->getId()) {
            $errors[] = SignalementAddressAnomaly::INCONSISTENT_TERRITORY;
        }

        return $errors;
    }
}
