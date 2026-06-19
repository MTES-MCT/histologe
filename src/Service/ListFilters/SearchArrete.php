<?php

namespace App\Service\ListFilters;

use App\Entity\Territory;
use App\Entity\User;
use App\Service\Behaviour\SearchQueryTrait;

class SearchArrete
{
    use SearchQueryTrait {
        getUrlParams as getUrlParamsBase;
    }

    private User $user;
    private ?Territory $territory = null;

    private ?string $autocompleteAddress = null;
    private ?string $housenumber = null;
    private ?string $street = null;
    private ?string $postCode = null;
    private ?string $city = null;
    private ?string $cityCode = null;

    /** @var array<string>|null */
    private ?array $typeArretes = null;
    private ?bool $mainLevee = null;
    private ?string $orderType = null;

    public function __construct(User $user)
    {
        $this->user = $user;
        if (!$user->isSuperAdmin() && 1 === count($user->getPartnersTerritories())) {
            $this->territory = $user->getFirstTerritory();
        }
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): void
    {
        $this->territory = $territory;
    }

    public function getAutocompleteAddress(): ?string
    {
        return $this->autocompleteAddress;
    }

    public function setAutocompleteAddress(?string $autocompleteAddress): void
    {
        $this->autocompleteAddress = $autocompleteAddress;
    }

    public function getHousenumber(): ?string
    {
        return $this->housenumber;
    }

    public function setHousenumber(?string $housenumber): void
    {
        $this->housenumber = $housenumber;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): void
    {
        $this->street = $street;
    }

    public function getPostCode(): ?string
    {
        return $this->postCode;
    }

    public function setPostCode(?string $postCode): void
    {
        $this->postCode = $postCode;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getCityCode(): ?string
    {
        return $this->cityCode;
    }

    public function setCityCode(?string $cityCode): void
    {
        $this->cityCode = $cityCode;
    }

    /**
     * @return array<string>|null
     */
    public function getTypeArretes(): ?array
    {
        return $this->typeArretes;
    }

    /**
     * @param array<string>|null $typeArretes
     */
    public function setTypeArretes(?array $typeArretes): void
    {
        $this->typeArretes = $typeArretes;
    }

    public function getMainLevee(): ?bool
    {
        return $this->mainLevee;
    }

    public function setMainLevee(?bool $mainLevee): void
    {
        $this->mainLevee = $mainLevee;
    }

    public function getOrderType(): ?string
    {
        return $this->orderType;
    }

    public function setOrderType(?string $orderType): void
    {
        $this->orderType = $orderType;
    }

    /**
     * @return array<mixed>
     */
    public function getUrlParams(): array
    {
        $params = $this->getUrlParamsBase();
        if (isset($params['territory']) && !$this->getUser()->isSuperAdmin()) {
            unset($params['territory']);
        }

        return $params;
    }
}
