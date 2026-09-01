<?php

namespace App\Factory;

use App\Entity\Address;
use App\Exception\Address\TerritoryNotFoundForCityCodeException;
use App\Service\Signalement\ZipcodeProvider;
use LongitudeOne\Spatial\PHP\Types\Geometry\Point;

class AddressFactory
{
    public function __construct(
        private readonly ZipcodeProvider $zipcodeProvider,
    ) {
    }

    public function createInstance(
        ?string $housenumber,
        string $street,
        string $postCode,
        string $city,
        string $cityCode,
        ?string $banId = null,
        ?float $longitude = null,
        ?float $latitude = null,
    ): Address {
        $territory = $this->zipcodeProvider->getTerritoryByInseeCode($cityCode);
        if (null === $territory) {
            throw new TerritoryNotFoundForCityCodeException($cityCode);
        }

        $address = (new Address())
            ->setHousenumber($housenumber ?: null)
            ->setStreet($street)
            ->setPostCode($postCode)
            ->setCity($city)
            ->setCityCode($cityCode)
            ->setTerritory($territory)
            ->setBanId($banId);

        if ($longitude && $latitude) {
            $address->setPoint(new Point($longitude, $latitude));
        }

        return $address;
    }
}
