<?php

namespace App\Service\ListFilters;

use App\Entity\Territory;
use App\Entity\User;
use App\Service\Behaviour\SearchQueryTrait;

class SearchTag
{
    use SearchQueryTrait {
        getUrlParams as getUrlParamsBase;
    }

    private User $user;
    private ?string $queryTag = null;
    private ?Territory $territory = null;
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

    public function getQueryTag(): ?string
    {
        return $this->queryTag;
    }

    public function setQueryTag(?string $queryTag): void
    {
        $this->queryTag = $queryTag;
    }

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): void
    {
        $this->territory = $territory;
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

    /**
     * @return array<mixed>
     */
    public function getFiltersToText(): array
    {
        $filters = [];
        if ($this->queryTag) {
            $filters['Recherche'] = $this->queryTag;
        }
        if ($this->territory && $this->user->isSuperAdmin()) {
            $filters['Territoire'] = $this->territory->getZipAndName();
        }

        return $filters;
    }
}
