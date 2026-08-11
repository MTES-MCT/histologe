<?php

namespace App\Factory;

use App\Entity\Arrete;
use App\Entity\Enum\ArreteType;
use App\Entity\User;
use App\Exception\Address\TerritoryNotFoundForCityCodeException;
use App\Repository\AddressRepository;
use App\Service\Gouv\Ban\AddressService;
use App\Service\Gouv\Rnb\RnbService;
use App\Service\Import\Arrete\ArreteImportRow;
use LongitudeOne\Spatial\Exception\InvalidValueException;

class ArreteFactory
{
    public function __construct(
        private readonly AddressService $addressService,
        private readonly AddressRepository $addressRepository,
        private readonly AddressFactory $addressFactory,
    ) {
    }

    /**
     * @throws InvalidValueException
     */
    public function createInstanceFrom(ArreteImportRow $arreteImportRow, User $user): ?Arrete
    {
        if (!$addressResponse = $this->addressService->getAcceptableBanAddress($arreteImportRow->getAddress())) {
            return null;
        }

        $housenumber = $addressResponse->getHousenumber() ?? $arreteImportRow->getNumeroVoie();
        $street = $addressResponse->getStreet() ?? $arreteImportRow->getNomVoie();
        $postCode = $addressResponse->getZipCode() ?? $arreteImportRow->getCodePostal();
        $city = $addressResponse->getCity() ?? $arreteImportRow->getCommune();
        $banId = $addressResponse->getBanId();

        if (null === $rnbBuilding) {
            $longitude = $addressResponse->getLongitude();
            $latitude = $addressResponse->getLatitude();
        } else {
            $longitude = $rnbBuilding->getLng();
            $latitude = $rnbBuilding->getLat();
        }

        $address = null;

        if (null === $cityCode) {
            return null;
        }

        if ($banId) {
            $address = $this->addressRepository->findOneBy(['banId' => $banId]);
        }

        if (null === $address) {
            $address = $this->addressRepository->findOneBy([
                'housenumber' => $housenumber,
                'street' => $street,
                'postCode' => $postCode,
                'cityCode' => $cityCode,
            ]);
        }

        if (null === $address) {
            try {
                $address = $this->addressFactory->createInstance(
                    $housenumber,
                    $street,
                    $postCode,
                    $city,
                    $cityCode,
                    $banId,
                    $longitude,
                    $latitude
                );
            } catch (TerritoryNotFoundForCityCodeException) {
                return null;
            }
        }

        if (!$user->isSuperAdmin() && $address->getTerritory()->getId() !== $user->getFirstTerritory()->getId()) {
            return null;
        }

        $arrete = new Arrete()
            ->setDateArrete($arreteImportRow->getDateArrete())
            ->setArreteType(ArreteType::tryFromLabel($arreteImportRow->getClassificationArrete()))
            ->setSyndic($arreteImportRow->getDenominationSyndic())
            ->setAddress($address)
            ->setIdentifiantParcellaire($arreteImportRow->getIdentifiantParcellaire())
            ->setImportedAt(new \DateTimeImmutable())
            ->setCreatedBy($user);

        if ($arreteImportRow->getDateArreteMainLevee()) {
            $arrete->setDateMainLevee($arreteImportRow->getDateArreteMainLevee());
        }

        return $arrete;
    }
}
