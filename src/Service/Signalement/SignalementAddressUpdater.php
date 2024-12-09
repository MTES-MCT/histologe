<?php

namespace App\Service\Signalement;

use App\Entity\Signalement;
use App\Service\BetaGouv\RnbService;
use App\Service\DataGouv\AddressService;

class SignalementAddressUpdater
{
    private const float SCORE_IF_BAN_ID_ACCEPTED = 0.9;

    public function __construct(
        private readonly AddressService $addressService,
        private readonly RnbService $rnbService,
    ) {
    }

    public function updateAddressOccupantFromBanData(Signalement $signalement, bool $updateGeolocAndBatId = true): void
    {
        $addressResult = $this->addressService->getAddress($signalement->getAddressCompleteOccupant());
        if ($addressResult->getScore() > self::SCORE_IF_BAN_ID_ACCEPTED) {
            $signalement->setBanIdOccupant($addressResult->getBanId());
            if ($updateGeolocAndBatId) {
                $signalement
                    ->setInseeOccupant($addressResult->getInseeCode())
                    ->setGeoloc([
                        'lat' => $addressResult->getLatitude(),
                        'lng' => $addressResult->getLongitude(),
                    ]);
                $buildings = $this->rnbService->getBuildings($signalement->getBanIdOccupant());
                $signalement->setRnbIdOccupant('');
                if (1 === count($buildings)) {
                    $signalement->setRnbIdOccupant($buildings[0]->getRnbId());
                }
            }

            return;
        } elseif ($updateGeolocAndBatId && !empty($signalement->getCpOccupant()) && !empty($signalement->getVilleOccupant())) {
            $inseeResult = $this->addressService->getAddress($signalement->getCpOccupant().' '.$signalement->getVilleOccupant());
            if (!empty($inseeResult->getCity())) {
                $signalement
                    ->setBanIdOccupant(0)
                    ->setVilleOccupant($inseeResult->getCity())
                    ->setInseeOccupant($inseeResult->getInseeCode())
                    ->setGeoloc([
                        'lat' => $inseeResult->getLatitude(),
                        'lng' => $inseeResult->getLongitude(),
                    ]);

                return;
            }
        }

        $signalement->setBanIdOccupant(0);
        if ($updateGeolocAndBatId) {
            $signalement
                ->setInseeOccupant(null)
                ->setGeoloc([]);
        }
    }
}
