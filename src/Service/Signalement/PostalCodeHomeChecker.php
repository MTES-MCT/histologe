<?php

namespace App\Service\Signalement;

use App\Entity\Territory;
use App\Repository\CommuneRepository;

class PostalCodeHomeChecker
{
    public function __construct(
        private readonly ZipcodeProvider $zipcodeProvider,
        private readonly CommuneRepository $communeRepository,
    ) {
    }

    public function getActiveTerritory(string $inseeCode): ?Territory
    {
        $territory = $this->zipcodeProvider->getTerritoryByInseeCode($inseeCode);
        if (!$territory || !$territory->isIsActive()) {
            return null;
        }

        return $this->isAuthorizedInseeCode($territory, $inseeCode) ? $territory : null;
    }

    public function isActiveByInseeCode(string $inseeCode): bool
    {
        return $this->getActiveTerritory($inseeCode) ? true : false;
    }

    public function isActiveByPostalCode(string $postalCode): bool
    {
        $communes = $this->communeRepository->findBy(['codePostal' => $postalCode]);
        foreach ($communes as $commune) {
            if ($this->isActiveByInseeCode($commune->getCodeInsee())) {
                return true;
            }
        }

        return false;
    }

    public function isAuthorizedInseeCode(Territory $territory, string $inseeCode): bool
    {
        $authorizedCodesInsee = $territory->getAuthorizedCodesInsee();
        if (empty($authorizedCodesInsee) || 0 == \count($authorizedCodesInsee)) {
            return true;
        }

        if (\in_array($inseeCode, $authorizedCodesInsee)) {
            return true;
        }

        return false;
    }

    public function normalizeInseeCode(string $postalCode, string $inseeCode): string
    {
        // Exception that returns city code insee for Marseille and Lyon
        // https://data.geopf.fr/geocodage/search/?q=13001%20Marseille
        // https://data.geopf.fr/geocodage/search/?q=69001%20Lyon
        $postalCodeInseeMapping = [
            '13001' => ['13055' => '13201'],
            '69001' => ['69123' => '69381'],
        ];

        return $postalCodeInseeMapping[$postalCode][$inseeCode] ?? $inseeCode;
    }
}
