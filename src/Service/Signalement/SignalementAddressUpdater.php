<?php

namespace App\Service\Signalement;

use App\Entity\Signalement;
use App\Service\Gouv\Ban\AddressService;
use App\Service\Gouv\Rial\RialService;
use App\Service\Gouv\Rnb\RnbService;

class SignalementAddressUpdater
{
    private const float SCORE_IF_BAN_ID_ACCEPTED = 0.9;

    public function __construct(
        private readonly AddressService $addressService,
        private readonly RnbService $rnbService,
        private readonly RialService $rialService,
    ) {
    }

    public function updateAddressOccupantFromBanData(Signalement $signalement, bool $updateGeolocAndRnbId = true): void
    {
        $addressResult = $this->addressService->getAddress($signalement->getAddressCompleteOccupant(false));
        if ($addressResult->getScore() > self::SCORE_IF_BAN_ID_ACCEPTED) {
            $signalement->setBanIdOccupant($addressResult->getBanId());
            if ($updateGeolocAndRnbId) {
                $signalement
                    ->setAdresseOccupant($addressResult->getStreet())
                    ->setVilleOccupant($addressResult->getCity())
                    ->setCpOccupant($addressResult->getZipCode())
                    ->setInseeOccupant($addressResult->getInseeCode())
                    ->setGeoloc([
                        'lat' => $addressResult->getLatitude(),
                        'lng' => $addressResult->getLongitude(),
                    ]);
                $buildings = $this->rnbService->getBuildings($signalement->getBanIdOccupant());
                $signalement->setRnbIdOccupant('');
                if (1 === \count($buildings)) {
                    $signalement->setRnbIdOccupant($buildings[0]->getRnbId());
                }
                $rialResult = $this->rialService->getSingleInvariantByBanId($signalement->getBanIdOccupant());
                if ($rialResult) {
                    $signalement->setNumeroInvariantRial($rialResult);
                } else {
                    $signalement->setNumeroInvariantRial(null);
                }
            }

            return;
        } elseif ($updateGeolocAndRnbId && !empty($signalement->getCpOccupant()) && !empty($signalement->getVilleOccupant())) {
            $inseeResult = $this->addressService->getAddress($signalement->getCpOccupant().' '.$signalement->getVilleOccupant());
            if (!empty($inseeResult->getCity())) {
                $signalement
                    ->setBanIdOccupant('0')
                    ->setRnbIdOccupant(null)
                    ->setVilleOccupant($inseeResult->getCity())
                    ->setCpOccupant($inseeResult->getZipCode())
                    ->setInseeOccupant($inseeResult->getInseeCode())
                    ->setGeoloc([]);

                return;
            }
        }

        $signalement->setBanIdOccupant('0');
        $signalement->setRnbIdOccupant(null);
        if ($updateGeolocAndRnbId) {
            $signalement
                ->setInseeOccupant(null)
                ->setGeoloc([]);
        }
    }
}
