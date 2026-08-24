<?php

namespace App\Dto;

use App\Entity\Signalement;
use Symfony\Component\Serializer\Attribute\Groups;

#[Groups(['signalements:read'])]
class AddressesHistoryListView
{
    public const string SEPARATOR_CONCAT = '||';
    public const string SEPARATOR_GROUP_CONCAT = ';';
    public const int MAX_LIST_PAGINATION = 30;

    /**
     * @param array<mixed> $signalements
     * @param array<mixed> $arretes
     * @param array<mixed> $bailleurNames
     */
    public function __construct(
        private readonly ?string $address = null,
        private readonly ?string $cp = null,
        private readonly ?string $ville = null,
        private readonly ?int $territoryId = null,
        private readonly ?string $addressForHuman = null,
        private readonly ?string $communeForHuman = null,
        private ?string $lat = null,
        private ?string $lng = null,
        private bool $hasLogementSocial = false,
        private bool $hasLogementPrive = false,
        private ?array $signalements = null,
        private ?array $arretes = null,
        private ?array $bailleurNames = [],
    ) {
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getCp(): ?string
    {
        return $this->cp;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function getTerritoryId(): ?int
    {
        return $this->territoryId;
    }

    public function getAddressForHuman(): ?string
    {
        return $this->addressForHuman;
    }

    public function getCommuneForHuman(): ?string
    {
        return $this->communeForHuman;
    }

    public function getLat(): ?string
    {
        return $this->lat;
    }

    public function setLat(?string $lat): void
    {
        $this->lat = $lat;
    }

    public function getLng(): ?string
    {
        return $this->lng;
    }

    public function setLng(?string $lng): void
    {
        $this->lng = $lng;
    }

    public function hasLogementSocial(): bool
    {
        return $this->hasLogementSocial;
    }

    public function setHasLogementSocial(bool $hasLogementSocial): void
    {
        $this->hasLogementSocial = $hasLogementSocial;
    }

    public function hasLogementPrive(): bool
    {
        return $this->hasLogementPrive;
    }

    public function setHasLogementPrive(bool $hasLogementPrive): void
    {
        $this->hasLogementPrive = $hasLogementPrive;
    }

    /** @return array<Signalement> */
    public function getSignalements(): ?array
    {
        return $this->signalements;
    }

    public function addSignalement(AddressesHistorySignalementView $addressesHistorySignalement): void
    {
        if (null === $this->signalements) {
            $this->signalements = [];
        }
        $this->signalements[] = $addressesHistorySignalement;
    }

    /** @return array<mixed> */
    public function getArretes(): ?array
    {
        return $this->arretes;
    }

    /**
     * @param array<mixed> $arrete
     */
    public function addArrete(array $arrete): void
    {
        if (null === $this->arretes) {
            $this->arretes = [];
        }
        $this->arretes[] = $arrete;
    }

    /** @return array<string> */
    public function getBailleurNames(): ?array
    {
        return $this->bailleurNames;
    }

    public function addBailleurName(string $bailleurName): void
    {
        $this->bailleurNames[] = $bailleurName;
        $this->bailleurNames = array_unique($this->bailleurNames, \SORT_STRING);
    }
}
