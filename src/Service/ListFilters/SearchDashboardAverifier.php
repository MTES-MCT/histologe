<?php

namespace App\Service\ListFilters;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\Behaviour\SearchQueryTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class SearchDashboardAverifier
{
    use SearchQueryTrait {
        getUrlParams as getUrlParamsBase;
    }

    private User $user;
    private ?string $queryCommune = null;
    private ?Territory $territory = null;
    /** @var Collection<int, Partner> */
    private Collection $partners;

    public function __construct(User $user)
    {
        $this->user = $user;
        if (!$user->isSuperAdmin() && 1 === count($user->getPartnersTerritories())) {
            $this->territory = $user->getFirstTerritory();
        }
        $this->partners = new ArrayCollection();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getQueryCommune(): ?string
    {
        return $this->queryCommune;
    }

    public function setQueryCommune(?string $queryCommune): void
    {
        $this->queryCommune = $queryCommune;
    }

    /**
     * @return Collection<int, Partner>
     */
    public function getPartners(): Collection
    {
        return $this->partners;
    }

    /**
     * @param Collection<int, Partner> $partners
     */
    public function setPartners(Collection $partners): void
    {
        $this->partners = $partners;
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
