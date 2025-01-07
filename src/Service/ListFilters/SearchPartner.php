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
    private ?Territory $territory = null;
    private ?PartnerType $partnerType = null;

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

    public function getQueryPartner(): ?string
    {
        return $this->queryPartner;
    }

    public function setQueryPartner(?string $queryPartner): void
    {
        $this->queryPartner = $queryPartner;
    }

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): void
    {
        $this->territory = $territory;
    }

    public function getPartnerType(): ?PartnerType
    {
        return $this->partnerType;
    }

    public function setPartnerType(PartnerType $partnerType): void
    {
        $this->partnerType = $partnerType;
    }

    public function getUrlParams(): array
    {
        $params = $this->getUrlParamsBase();
        if (isset($params['territory']) && !$this->getUser()->isSuperAdmin()) {
            unset($params['territory']);
        }

        return $params;
    }

    public function getFiltersToText(): array
    {
        $filters = [];
        if ($this->queryPartner) {
            $filters['Recherche'] = $this->queryPartner;
        }
        if ($this->territory && $this->user->isSuperAdmin()) {
            $filters['Territoire'] = $this->territory->getZip().' - '.$this->territory->getName();
        }
        if (null !== $this->partnerType) {
            $filters['Type de partenaire'] = $this->partnerType->label();
        }

        return $filters;
    }
}
