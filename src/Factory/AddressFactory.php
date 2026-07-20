<?php

namespace App\Factory;

use App\Entity\Address;
use App\Entity\Signalement;
use LongitudeOne\Spatial\PHP\Types\Geometry\Point;

class AddressFactory
{
    public function __construct()
    {
    }

    public function createInstanceFrom(
        Signalement $signalement,
        ?string $housenumber,
        string $street,
    ): Address {
        $address = new Address();
        $address
            ->setHousenumber($housenumber)
            ->setStreet($street)
            ->setCityCode($signalement->getInseeOccupant())
            ->setCity($signalement->getVilleOccupant())
            ->setPostCode($signalement->getCpOccupant())
            ->setTerritory($signalement->getTerritory());

        // Ne définir le banId que s'il n'est pas vide
        if (!empty($signalement->getBanIdOccupant())) {
            $address->setBanId($signalement->getBanIdOccupant());
        }

        // Convertir geoloc JSON en point PostGIS
        $point = null;
        if (!empty($signalement->getGeoloc())) {
            $geoloc = $signalement->getGeoloc();
            if (isset($geoloc['lat']) && isset($geoloc['lng'])) {
                $lat = $geoloc['lat'];
                $lng = $geoloc['lng'];
                $point = new Point($lat, $lng);
                $address->setPoint($point);
            }
        }

        return $address;
    }
}
