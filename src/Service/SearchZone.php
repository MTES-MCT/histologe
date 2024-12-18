<?php

namespace App\Service;

use App\Entity\Enum\ZoneType;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\Behaviour\SearchQueryTrait;

class SearchZone
{
    use SearchQueryTrait {
        getUrlParams as getUrlParamsBase;
    }
    private User $user;
    private ?string $queryName = null;
    private ?Territory $territory = null;
    private ?ZoneType $type = null;

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

    public function getQueryName(): ?string
    {
        return $this->queryName;
    }

    public function setQueryName(?string $queryName): void
    {
        $this->queryName = $queryName;
    }

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): void
    {
        $this->territory = $territory;
    }

    public function getType(): ?ZoneType
    {
        return $this->type;
    }

    public function setType(?ZoneType $type): void
    {
        $this->type = $type;
    }

    public function getUrlParams(): array
    {
        $params = $this->getUrlParamsBase();
        if (isset($params['territory']) && !$this->getUser()->isSuperAdmin()) {
            unset($params['territory']);
        }

        return $params;
    }
}
