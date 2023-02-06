<?php

namespace App\Service\Signalement;

use App\Entity\Territory;
use App\Repository\TerritoryRepository;

class PostalCodeHomeChecker
{
    public const CORSE_DU_SUD_CODE_DEPARTMENT_2A = '2A';
    public const HAUTE_CORSE_CODE_DEPARTMENT_2B = '2B';
    public const LA_REUNION_CODE_DEPARTMENT_974 = '974';

    public function __construct(private TerritoryRepository $territoryRepository)
    {
    }

    public function isActive(string $postalCode, string $inseeCode = ''): bool
    {
        $territoryItem = $this->territoryRepository->findOneBy([
            'zip' => $this->getZipCode($postalCode),
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

    public function getZipCode(string $postalCode): string
    {
        $zipChunk = substr($postalCode, 0, 3);

        return match ($zipChunk) {
            '200', '201' => self::CORSE_DU_SUD_CODE_DEPARTMENT_2A,
            '202', '206' => self::HAUTE_CORSE_CODE_DEPARTMENT_2B,
            '974' => self::LA_REUNION_CODE_DEPARTMENT_974,
            default => substr($postalCode, 0, 2),
        };
    }

    public function isAuthorizedInseeCode(Territory $territory, string $inseeCode)
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
