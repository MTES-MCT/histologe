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

    public function isActive(string $postalCode, ?string $inseeCode = null): bool
    {
        // Si on a un code Insee, on vérifie en priorité celui-ci car il indique le vrai territoire
        $codeToCheck = $inseeCode ?? $postalCode;
        $territoryItem = $this->territoryRepository->findOneBy([
            'zip' => ZipcodeProvider::getZipCode($codeToCheck),
            'isActive' => 1,
        ]);

        if (!empty($territoryItem)) {
            if (empty($inseeCode)) {
                return true;
            }

            return $this->isAuthorizedInseeCode($territoryItem, $inseeCode);
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
}
