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

    public function updateAddressOccupantFromBanData(Signalement $signalement, bool $updateRnbId = true): void
    {
        $addressResult = $this->addressService->getAddress($signalement->getAddressCompleteOccupant(false));
        if ($addressResult->getScore() > self::SCORE_IF_BAN_ID_ACCEPTED) {
            $signalement
                ->setBanIdOccupant($addressResult->getBanId())
                ->setAdresseOccupant($addressResult->getStreet())
                ->setVilleOccupant($addressResult->getCity())
                ->setCpOccupant($addressResult->getZipCode())
                ->setInseeOccupant($addressResult->getInseeCode())
                ->setGeoloc([
                    'lat' => $addressResult->getLatitude(),
                    'lng' => $addressResult->getLongitude(),
                ]);

            if ($updateRnbId) {
                $buildings = $this->rnbService->getBuildings($signalement->getBanIdOccupant());
                $signalement->setRnbIdOccupant('');
                if (1 === \count($buildings)) {
                    $signalement
                        ->setRnbIdOccupant($buildings[0]->getRnbId())
                        ->setGeoloc([
                            'lat' => $buildings[0]->getLat(),
                            'lng' => $buildings[0]->getLng(),
                        ]);
                }
            } else {
                $this->updateGeolocFromRnbService($signalement);
            }
            $rialResult = $this->rialService->getSingleInvariantByBanId($signalement->getBanIdOccupant());
            if ($rialResult) {
                $signalement->setNumeroInvariantRial($rialResult);
            } else {
                $signalement->setNumeroInvariantRial(null);
            }

            return;
        } elseif (!empty($signalement->getCpOccupant()) && !empty($signalement->getVilleOccupant())) {
            $inseeResult = $this->addressService->getAddress($signalement->getCpOccupant().' '.$signalement->getVilleOccupant());
            if (!empty($inseeResult->getCity())) {
                $signalement
                    ->setBanIdOccupant('0')
                    ->setVilleOccupant($inseeResult->getCity())
                    ->setCpOccupant($inseeResult->getZipCode())
                    ->setInseeOccupant($inseeResult->getInseeCode())
                    ->setGeoloc([]);
                if ($updateRnbId) {
                    $signalement->setRnbIdOccupant(null);
                } else {
                    $this->updateGeolocFromRnbService($signalement);
                }

                return;
            }
        }

        $signalement->setBanIdOccupant('0');
        if ($updateRnbId) {
            $signalement->setRnbIdOccupant(null);
        } else {
            $this->updateGeolocFromRnbService($signalement);
        }
        $signalement
            ->setInseeOccupant(null)
            ->setGeoloc([]);
    }

    private function updateGeolocFromRnbService(Signalement $signalement): void
    {
        // Si on vient du formulaire front, et qu'on a déjà un RNB ID, on ne le met pas à jour ($updateRnbId est false)
        // Par contre, on refait un appel à rnbservice pour mettre à jour la géoloc spécifique du bâtiment
        if ($signalement->getRnbIdOccupant()) {
            $building = $this->rnbService->getBuilding($signalement->getRnbIdOccupant());

            if ($building && $building->getRnbId()) {
                $signalement->setGeoloc(['lat' => $building->getLat(), 'lng' => $building->getLng()]);
            }
        }
    }
}
