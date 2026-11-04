<?php

namespace App\Factory;

use App\Entity\Address;
use App\Entity\Arrete;
use App\Entity\Enum\ArreteCreationSource;
use App\Entity\Enum\ArreteType;
use App\Entity\Signalement;
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
        private readonly RnbService $rnbService,
        private readonly AddressFactory $addressFactory,
    ) {
    }

    /**
     * @param array<mixed> $additionalInformation
     */
    public function createInstance(
        \DateTimeImmutable $dateArrete,
        ArreteType $arreteType,
        Address $address,
        ?string $syndic = null,
        ?string $identifiantParcellaire = null,
        ?\DateTimeImmutable $dateMainLevee = null,
        ?User $createdBy = null,
        array $additionalInformation = [],
        ?Signalement $signalement = null,
        ArreteCreationSource $arreteCreationSource = ArreteCreationSource::IMPORT,
        ?int $providerId = null,
    ): Arrete {
        $arrete = new Arrete()
            ->setDateArrete($dateArrete)
            ->setArreteType($arreteType)
            ->setAddress($address)
            ->setSyndic($syndic)
            ->setIdentifiantParcellaire($identifiantParcellaire)
            ->setImportedAt(new \DateTimeImmutable())
            ->setCreatedBy($createdBy)
            ->setAdditionalInformation($additionalInformation)
            ->setSignalement($signalement)
            ->setArreteCreationSource($arreteCreationSource)
            ->setProviderId($providerId);

        if ($dateMainLevee) {
            $arrete->setDateMainLevee($dateMainLevee);
        }

        return $arrete;
    }

    /**
     * @throws InvalidValueException
     */
    public function createInstanceFrom(ArreteImportRow $arreteImportRow, User $user): ?Arrete
    {
        $rnbBuilding = !empty($arreteImportRow->getRnbId()) ? $this->rnbService->getBuilding($arreteImportRow->getRnbId()) : null;
        $addressResponse = $this->addressService->getAddress($arreteImportRow->getAddress());
        $hasValidBan = $addressResponse->getScore() >= AddressService::SCORE_IF_BAN_ID_ACCEPTED;

        if (!$hasValidBan && null === $rnbBuilding) {
            return null;
        }

        $cityCode = $addressResponse->getInseeCode();
        if (null === $cityCode) {
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

        if (!$user->isSuperAdmin() && $address->getTerritory()->getId() !== $user->getFirstTerritory()?->getId()) {
            return null;
        }

        $arrete = $this->createInstance(
            dateArrete: $arreteImportRow->getDateArrete(),
            arreteType: ArreteType::tryFromLabel($arreteImportRow->getClassificationArrete()),
            address: $address,
            syndic: $arreteImportRow->getDenominationSyndic(),
            identifiantParcellaire: $arreteImportRow->getIdentifiantParcellaire(),
            dateMainLevee: $arreteImportRow->getDateArreteMainLevee(),
            createdBy: $user,
        );

        return $arrete;
    }
}
