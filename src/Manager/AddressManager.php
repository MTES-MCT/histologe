<?php

namespace App\Manager;

use App\Entity\Address;
use App\Entity\Signalement;
use App\Factory\AddressFactory;
use App\Repository\AddressRepository;
use App\Utils\Address\AddressParser;
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
        // Si le signalement n'a pas d'adresse, retourner une adresse vide (ne devrait pas arriver)
        if (empty($signalement->getAdresseOccupant())) {
            throw new \InvalidArgumentException('Le signalement doit avoir une adresse occupant');
        }

        // Extraire le numéro de rue et le nom de la rue depuis adresseOccupant
        $parsedAddress = AddressParser::parse($signalement->getAdresseOccupant());
        $housenumber = $parsedAddress['number'].$parsedAddress['suffix'];
        $street = $parsedAddress['street'];

        // Vérifier d'abord par banId si disponible
        $existingAddress = null;
        if (!empty($signalement->getBanIdOccupant())) {
            $existingAddress = $this->addressRepository->findOneBy(['banId' => $signalement->getBanIdOccupant()]);
        }

        // Si pas trouvé par banId, vérifier par l'adresse (contrainte unique sur housenumber, street, city_code)
        if (!$existingAddress) {
            $checkParams = [
                'street' => $street,
                'cityCode' => $signalement->getInseeOccupant(),
            ];

            if ($housenumber) {
                $checkParams['housenumber'] = $housenumber;
            }

            $existingAddress = $this->addressRepository->findOneBy($checkParams);
        }

        // Insérer uniquement si l'adresse n'existe pas déjà
        if (!$existingAddress) {
            $address = $this->addressFactory->createInstanceFrom($signalement, $housenumber, $street);
            $this->entityManager->persist($address);

            return $address;
        }
        $save = false;
        // Si l'adresse existante n'a pas de banId, on peut le mettre à jour
        // (on sait qu'il n'est pas déjà utilisé car on a cherché par banId au début)
        if (empty($existingAddress->getBanId()) && !empty($signalement->getBanIdOccupant())) {
            $existingAddress->setBanId($signalement->getBanIdOccupant());
            $save = true;
        }
        if (empty($existingAddress->getPoint()) && !empty($signalement->getGeoloc())) {
            $geoloc = $signalement->getGeoloc();
            if (isset($geoloc['lat']) && isset($geoloc['lng'])) {
                $lat = $geoloc['lat'];
                $lng = $geoloc['lng'];
                // Point prend (longitude, latitude) selon le standard spatial
                $point = new Point($lng, $lat);
                $existingAddress->setPoint($point);
                $save = true;
            }
        }
        if ($save) {
            $this->entityManager->persist($existingAddress);
        }

        return $existingAddress;
    }
}
