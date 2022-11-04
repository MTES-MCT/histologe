<?php

namespace App\Service\Signalement;

use App\Entity\Territory;
use App\Repository\TerritoryRepository;

class PostalCodeHomeChecker
{
    public const CORSE_DEPARTMENT = ['20' => '2A'];

    public const DOM_TOM_START_WITH_97 = '97';

    public function __construct(private TerritoryRepository $territoryRepository)
    {
    }

    public function isActive(string $postalCode): bool
    {
        $territoryItems = $this->territoryRepository->findOneBy([
            'zip' => $this->mapZip($postalCode),
            'isActive' => 1,
        ]);

        if (!empty($territoryItems)) {
            return true;
        }

        return false;
    }

    public function mapZip(string $postalCode): string
    {
        $zip = substr($postalCode, 0, str_starts_with($postalCode, self::DOM_TOM_START_WITH_97) ? 3 : 2);

        return \array_key_exists($zip, self::CORSE_DEPARTMENT) ? self::CORSE_DEPARTMENT[$zip] : $zip;
    }

    public function isAuthorizedInseeCode(Territory $territory, string $inseeCode)
    {
        if (0 == \count($territory->getAuthorizedCodesInsee())) {
            return true;
        }

        if (\in_array($inseeCode, $territory->getAuthorizedCodesInsee())) {
            return true;
        }

        return false;
    }
}
