<?php

namespace App\Service\ListFilters;

use App\Entity\Enum\PartnerType;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\Behaviour\SearchQueryTrait;

class SearchPartner
{
    use SearchQueryTrait {
        getUrlParams as getUrlParamsBase;
    }

    private User $user;
    private ?string $queryPartner = null;
    private ?Territory $territoire = null;
    private ?PartnerType $partnerType = null;
    private ?string $orderType = null;
    private ?bool $isNotNotifiable = null;

    public function __construct(User $user)
    {
        $this->user = $user;
        if (!$user->isSuperAdmin() && 1 === count($user->getPartnersTerritories())) {
            $this->territoire = $user->getFirstTerritory();
        }
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getQueryPartner(): ?string
    {
        return $this->queryPartner;
    }

    public function setQueryPartner(?string $queryPartner): void
    {
        $this->queryPartner = $queryPartner;
    }

    public function getTerritoire(): ?Territory
    {
        return $this->territoire;
    }

    public function setTerritoire(?Territory $territoire): void
    {
        $this->territoire = $territoire;
    }

    public function getPartnerType(): ?PartnerType
    {
        return $this->partnerType;
    }

    public function setPartnerType(PartnerType $partnerType): void
    {
        $this->partnerType = $partnerType;
    }

    public function getOrderType(): ?string
    {
        return $this->orderType;
    }

    public function setOrderType(?string $orderType): void
    {
        $this->orderType = $orderType;
    }

    public function getIsNotNotifiable(): ?bool
    {
        return $this->isNotNotifiable;
    }

    public function setIsNotNotifiable(?bool $isNotNotifiable): void
    {
        $this->isNotNotifiable = $isNotNotifiable;
    }

    public function getUrlParams(): array
    {
        $params = $this->getUrlParamsBase();
        if (isset($params['territoire']) && !$this->getUser()->isSuperAdmin()) {
            unset($params['territoire']);
        }

        return $params;
    }

    public function getFiltersToText(): array
    {
        $filters = [];
        if ($this->queryPartner) {
            $filters['Recherche'] = $this->queryPartner;
        }
        if ($this->territoire && $this->user->isSuperAdmin()) {
            $filters['Territoire'] = $this->territoire->getZip().' - '.$this->territoire->getName();
        }
        if (null !== $this->partnerType) {
            $filters['Type de partenaire'] = $this->partnerType->label();
        }

        return $filters;
    }
}
