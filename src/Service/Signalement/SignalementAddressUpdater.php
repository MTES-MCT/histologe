<?php

namespace App\Service\Signalement;

use App\Entity\Signalement;
use App\Exception\Address\CityNotFoundException;
use App\Factory\AddressFactory;
use App\Repository\AddressRepository;
use App\Service\Gouv\Ban\AddressService;
use App\Service\Gouv\Ban\Response\BanAddress;
use App\Service\Gouv\Rial\RialService;
use App\Service\Gouv\Rnb\RnbService;
use App\Utils\Address\AddressParser;
use Doctrine\ORM\EntityManagerInterface;
use LongitudeOne\Spatial\PHP\Types\Geometry\Point;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SignalementAddressUpdater
{
    public function __construct(
        private readonly AddressRepository $addressRepository,
        private readonly AddressService $addressService,
        private readonly AddressFactory $addressFactory,
        private readonly EntityManagerInterface $entityManager,
        private readonly RnbService $rnbService,
        private readonly RialService $rialService,
        #[Autowire(env: 'RIAL_ENABLE')]
        private readonly string $rialEnable,
    ) {
    }

    public function attachAddressToSignalement(Signalement $signalement, string $address, string $postCode, string $city, ?string $rnbId = null): void
    {
        $adresseComplete = $address.' '.$postCode.' '.$city;
        if ($banAddress = $this->addressService->getAcceptableBanAddress($adresseComplete)) {
            $this->attachAddressToSignalementFromBanAddress($signalement, $banAddress);
        } else {
            $signalement->setRnbIdOccupant($rnbId);
            if (!$this->attachAddressToSignalementFromManualAddress($signalement, $address, $postCode, $city)) {
                throw new CityNotFoundException($city, $postCode);
            }
        }
    }

    public function attachAddressToSignalementFromBanAddress(Signalement $signalement, BanAddress $banAddress): void
    {
        $address = $this->addressRepository->findForBanAddress($banAddress);
        if ($address) {
            // on met a jour les infos (au cas ou l'adresse a été modifiée dans la BAN ou que l'adresse existait sans banId)
            $address->setHousenumber($banAddress->getHousenumber());
            $address->setStreet($banAddress->getStreet());
            // le changement de code postal est possible (des cas réels on été vu lors de la migration des adresses) cela impacte les informations de connexion au signalements concernés.
            // faut il prévoir un mécanisme de notification ? (en pratique ce cas est déja possible via l'édition d'adresse BO et rien ne le gère)
            $address->setPostCode($banAddress->getZipCode());
            $address->setCity($banAddress->getCity());
            $address->setCityCode($banAddress->getInseeCode());
            $address->setPoint(new Point($banAddress->getLongitude(), $banAddress->getLatitude()));
            $address->setBanId($banAddress->getBanId());
        } else {
            $address = $this->addressFactory->createInstance(
                $banAddress->getHousenumber(),
                $banAddress->getStreet(),
                $banAddress->getZipCode(),
                $banAddress->getCity(),
                $banAddress->getInseeCode(),
                $banAddress->getBanId(),
                $banAddress->getLongitude(),
                $banAddress->getLatitude()
            );
            $this->entityManager->persist($address);
        }
        $signalement->setAddress($address);
    }

    public function attachAddressToSignalementFromManualAddress(Signalement $signalement, string $numberAndStreet, string $postCode, string $city): bool
    {
        $addressResult = $this->addressService->getAddress($postCode.' '.$city);
        if (!$addressResult->getCity() || $addressResult->getZipCode() !== $postCode) {
            return false;
        }

        $parsedAddress = AddressParser::parse($numberAndStreet);
        $houseNumber = $parsedAddress['numberAndSuffix'];
        $street = mb_trim($parsedAddress['street']);
        $address = $this->addressRepository->findForManualAddress($houseNumber, $street, $addressResult->getZipCode(), $addressResult->getInseeCode());

        if (!$address) {
            $address = $this->addressFactory->createInstance(
                $houseNumber,
                $street,
                $addressResult->getZipCode(),
                $addressResult->getCity(),
                $addressResult->getInseeCode(),
            );
            $this->entityManager->persist($address);
        }
        $signalement->setAddress($address);

        return true;
    }

    public function getRnbDataForSignalement(Signalement $signalement): void
    {
        $signalement->setRnbIdOccupant(null);
        $signalement->setGeoloc([]);
        if ($signalement->getAddress()->getBanId()) {
            $buildings = $this->rnbService->getBuildings($signalement->getAddress()->getBanId());
            if (1 === \count($buildings)) {
                $signalement
                    ->setRnbIdOccupant($buildings[0]->getRnbId())
                    ->setGeoloc(['lat' => $buildings[0]->getLat(), 'lng' => $buildings[0]->getLng()]);
            }
        }
    }

    public function getRialDataForSignalement(Signalement $signalement): void
    {
        if (!$this->rialEnable) {
            return;
        }
        $signalement->setNumeroInvariantRial(null);
        if ($signalement->getAddress()->getBanId()) {
            $rialResult = $this->rialService->getSingleInvariantByBanId($signalement->getAddress()->getBanId());
            if ($rialResult) {
                $signalement->setNumeroInvariantRial($rialResult);
            }
        }
    }

    public function updateGeolocFromRnbService(Signalement $signalement): void
    {
        $building = $this->rnbService->getBuilding($signalement->getRnbIdOccupant());

        if ($building && $building->getRnbId()) {
            $signalement->setGeoloc(['lat' => $building->getLat(), 'lng' => $building->getLng()]);
        } else {
            $signalement->setRnbIdOccupant(null);
            $signalement->setGeoloc([]);
        }
    }
}
