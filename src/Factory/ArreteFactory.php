<?php

namespace App\Factory;

use App\Entity\Address;
use App\Entity\Arrete;
use App\Entity\Enum\ArreteType;
use App\Entity\User;
use App\Repository\AddressRepository;
use App\Service\Gouv\Ban\AddressService;
use App\Service\Import\Arrete\ArreteImportRow;
use App\Service\Signalement\ZipcodeProvider;
use LongitudeOne\Spatial\Exception\InvalidValueException;
use LongitudeOne\Spatial\PHP\Types\Geometry\Point;

class ArreteFactory
{
    public function __construct(
        private readonly AddressService $addressService,
        private readonly AddressRepository $addressRepository,
        private readonly ZipcodeProvider $zipcodeProvider,
    ) {
    }

    /**
     * @throws InvalidValueException
     */
    public function createInstanceFrom(ArreteImportRow $arreteImportRow, User $user): ?Arrete
    {
        $addressResponse = $this->addressService->getAddress($arreteImportRow->getAddress());
        if ($addressResponse->getScore() < AddressService::SCORE_IF_BAN_ID_ACCEPTED) {
            return null;
        }

        $housenumber = $addressResponse->getHousenumber() ?? $arreteImportRow->getNumeroVoie();
        $street = $addressResponse->getStreet(withHouseNumber: false) ?? $arreteImportRow->getNomVoie();
        $postCode = $addressResponse->getZipCode() ?? $arreteImportRow->getCodePostal();
        $city = $addressResponse->getCity() ?? $arreteImportRow->getCommune();
        $cityCode = $addressResponse->getInseeCode();
        $banId = $addressResponse->getBanId();
        $longitude = $addressResponse->getLongitude();
        $latitude = $addressResponse->getLatitude();
        $address = null;

        if (null === $cityCode) {
            return null;
        }
        $territoryAddress = $this->zipcodeProvider->getTerritoryByInseeCode($cityCode);

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
            $address = new Address()
                ->setHousenumber($housenumber)
                ->setStreet($street)
                ->setPostCode($postCode)
                ->setCity($city)
                ->setCityCode($cityCode)
                ->setBanId($banId);

            if ($longitude && $latitude) {
                $address->setPoint(new Point($longitude, $latitude));
            }

            if (null === $territoryAddress) {
                return null;
            }

            $address->setTerritory($territoryAddress);
        }

        if ($user->isTerritoryAdmin() && $territoryAddress->getId() !== $user->getFirstTerritory()->getId()) {
            return null;
        }

        $arrete = new Arrete()
            ->setDateArrete($arreteImportRow->getDateArrete())
            ->setTypeArrete(ArreteType::tryFromLabel($arreteImportRow->getClassificationArrete()))
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
