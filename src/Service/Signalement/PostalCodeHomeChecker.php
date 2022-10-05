<?php

namespace App\Service\Signalement;

use App\Repository\TerritoryRepository;

class PostalCodeHomeChecker
{
    public const CORSE_DEPARTMENT = ['20' => '2A', '21' => '2B'];

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
}
