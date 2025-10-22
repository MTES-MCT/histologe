<?php

namespace App\Service\ListFilters;

use App\Entity\Territory;
use App\Entity\User;
use App\Service\Behaviour\SearchQueryTrait;

class SearchSignalementInjonction
{
    use SearchQueryTrait {
        getUrlParams as getUrlParamsBase;
    }

    private User $user;
    private ?Territory $territoire = null;
    private ?string $orderType = null;
    private ?string $injonctionAvecAide = null;

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

    public function getTerritoire(): ?Territory
    {
        return $this->territoire;
    }

    public function setTerritoire(?Territory $territoire): void
    {
        $this->territoire = $territoire;
    }

    public function getInjonctionAvecAide(): ?string
    {
        return $this->injonctionAvecAide;
    }

    public function setInjonctionAvecAide(?string $injonctionAvecAide): void
    {
        $this->injonctionAvecAide = $injonctionAvecAide;
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
        if (isset($params['territoire']) && !$this->getUser()->isSuperAdmin()) {
            unset($params['territoire']);
        }

        return $params;
    }
}
