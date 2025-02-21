<?php

namespace App\Service\Signalement;

use App\Entity\Territory;
use App\Repository\TerritoryRepository;

class PostalCodeHomeChecker
{
    public function __construct(
        private readonly TerritoryRepository $territoryRepository,
    ) {
    }

    public function getActiveTerritory(string $postalCode, ?string $inseeCode = null): ?Territory
    {
        // Si on a un code Insee, on vérifie en priorité celui-ci car il indique le vrai territoire
        $codeToCheck = $inseeCode ?? $postalCode;
        $territoryItem = $this->territoryRepository->findOneBy([
            'zip' => ZipcodeProvider::getZipCode($codeToCheck),
            'isActive' => 1,
        ]);

        if (!empty($territoryItem)) {
            if (empty($inseeCode)) {
                return $territoryItem;
            }

            return $this->isAuthorizedInseeCode($territoryItem, $inseeCode) ? $territoryItem : null;
        }

        return null;
    }

    public function isActive(string $postalCode, ?string $inseeCode = null): bool
    {
        $activeTerritory = $this->getActiveTerritory($postalCode, $inseeCode);

        return $activeTerritory ? true : false;
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
}
