<?php

namespace App\Service\Signalement;

use App\Entity\Arrete;
use App\Entity\Signalement;
use App\Repository\ArreteRepository;
use App\Utils\Address\AddressParser;

class SignalementSameAddressArreteFinder
{
    public function __construct(
        private readonly ArreteRepository $arreteRepository,
    ) {
    }

    /**
     * @return Arrete[]
     */
    public function find(Signalement $signalement): array
    {
        $banId = $signalement->getBanIdOccupant();
        if (null !== $banId && '' !== $banId && '0' !== $banId) {
            $arretes = $this->arreteRepository->findByBanId($banId);

            if ([] !== $arretes) {
                return $arretes;
            }
        }

        $adresseOccupant = $signalement->getAdresseOccupant();
        $cpOccupant = $signalement->getCpOccupant();
        $inseeOccupant = $signalement->getInseeOccupant();
        if (null === $adresseOccupant || '' === $adresseOccupant
            || null === $cpOccupant || '' === $cpOccupant
            || null === $inseeOccupant || '' === $inseeOccupant
        ) {
            return [];
        }

        $address = AddressParser::parse($adresseOccupant);

        $houseNumber = $address['number'];
        if (null !== $address['suffix'] && null !== $address['number']) {
            $suffix = strtolower($address['suffix']);
            $houseNumber = [
                $address['number'].$suffix,
                $address['number'].' '.$suffix,
            ];
        }

        return $this->arreteRepository->findByAddress(
            housenumber: $houseNumber,
            street: $address['street'],
            postCode: $cpOccupant,
            cityCode: $inseeOccupant
        );
    }
}
