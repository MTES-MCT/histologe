<?php

namespace App\Service\Signalement;

use App\Entity\Enum\SignalementAddressAnomaly;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Repository\CommuneRepository;

class SignalementAddressAnomalyChecker
{
    private const string INSEE_FORMAT_PATTERN = '/^(\d{2}|2[AB])\d{3}$/';
    private const string CP_FORMAT_PATTERN = '/^\d{5}$/';

    public function __construct(
        private readonly ZipcodeProvider $zipcodeProvider,
        private readonly CommuneRepository $communeRepository,
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

        // le code INSEE fait 5 caractères mais n'est pas forcément 5 chiffres : la Corse utilise
        // "2A"/"2B" comme préfixe de département (ex: 2A004), les DROM restent numériques.
        $inseeValid = !$inseeOccupant || 1 === preg_match(self::INSEE_FORMAT_PATTERN, $inseeOccupant);
        if (!$inseeValid) {
            $errors[] = SignalementAddressAnomaly::INVALID_INSEE_FORMAT;
        }

        $cpValid = !$cpOccupant || 1 === preg_match(self::CP_FORMAT_PATTERN, $cpOccupant);
        if (!$cpValid) {
            $errors[] = SignalementAddressAnomaly::INVALID_CP_FORMAT;
        }

        // ne vérifie la cohérence de la paire que si les deux formats sont individuellement valides,
        // pour éviter une erreur redondante avec INVALID_INSEE_FORMAT / INVALID_CP_FORMAT.
        if ($inseeOccupant && $cpOccupant && $inseeValid && $cpValid
            && null === $this->communeRepository->findOneBy(['codePostal' => $cpOccupant, 'codeInsee' => $inseeOccupant])
        ) {
            $errors[] = SignalementAddressAnomaly::INCONSISTENT_CP_INSEE;
        }

        $calculatedTerritory = $this->getCalculatedTerritory($signalement);
        if ($calculatedTerritory && $signalement->getTerritory() && $calculatedTerritory->getId() !== $signalement->getTerritory()->getId()) {
            $errors[] = SignalementAddressAnomaly::INCONSISTENT_TERRITORY;
        }

        return $errors;
    }
}
