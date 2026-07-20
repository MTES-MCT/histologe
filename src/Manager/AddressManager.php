<?php

namespace App\Manager;

use App\Entity\Address;
use App\Entity\Signalement;
use App\Factory\AddressFactory;
use App\Repository\AddressRepository;
use App\Service\Address\AddressHelper;
use Doctrine\ORM\EntityManagerInterface;
use LongitudeOne\Spatial\PHP\Types\Geometry\Point;

class AddressManager
{
    public function __construct(
        private readonly AddressFactory $addressFactory,
        private readonly AddressRepository $addressRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function createOrUpdateFrom(
        Signalement $signalement,
    ): Address {
        // Extraire le numéro de rue et le nom de la rue depuis adresseOccupant
        [$housenumber, $street] = AddressHelper::getHouseNumberAndStreetFromAddress($signalement->getAdresseOccupant());

        // Vérifier si l'adresse existe déjà (contrainte unique sur housenumber, street, city_code)
        $checkParams = [
            'street' => $street,
            'cityCode' => $signalement->getInseeOccupant(),
        ];

        if ($housenumber) {
            $checkParams['housenumber'] = $housenumber;
        }

        $existingAddress = $this->addressRepository->findOneBy($checkParams);

        // Insérer uniquement si l'adresse n'existe pas déjà
        if (!$existingAddress) {
            $address = $this->addressFactory->createInstanceFrom($signalement, $housenumber, $street);
            $this->entityManager->persist($address);

            return $address;
        }
        $save = false;
        if (empty($existingAddress->getBanId())) {
            $existingAddress->setBanId($signalement->getBanIdOccupant());
            $save = true;
        }
        if (empty($existingAddress->getPoint()) && !empty($signalement->getGeoloc())) {
            $geoloc = $signalement->getGeoloc();
            if (isset($geoloc['lat']) && isset($geoloc['lng'])) {
                $lat = $geoloc['lat'];
                $lng = $geoloc['lng'];
                $point = new Point($lat, $lng);
                $existingAddress->setPoint($point);
                $save = false;
            }
        }
        if ($save) {
            $this->entityManager->persist($existingAddress);
        }

        return $existingAddress;
    }
}
