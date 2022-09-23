<?php

namespace App\Service;

use App\Repository\TerritoryRepository;

class PostalCodeHomeCheckerService
{
    public function __construct(private TerritoryRepository $territoryRepository)
    {
    }

    public function isActive(string $postal_code): bool
    {
        $zip = substr($postal_code, 0, 2);

        $territoryItems = $this->territoryRepository->findByZip($zip);
        if (!empty($territoryItems)) {
            return true;
        }

        return false;
    }
}
