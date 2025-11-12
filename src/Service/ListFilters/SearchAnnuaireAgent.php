<?php

namespace App\Service\ListFilters;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\Behaviour\SearchQueryTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class SearchAnnuaireAgent
{
    use SearchQueryTrait;
    private User $user;
    private ?string $queryUser = null;
    private ?Territory $territory = null;
    /** @var Collection<int, Partner> */
    private Collection $partners;
    private ?string $orderType = null;

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

    public function getQueryUser(): ?string
    {
        return $this->queryUser;
    }

    public function setQueryUser(?string $queryUser): void
    {
        $this->queryUser = $queryUser;
    }

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): void
    {
        $this->territory = $territory;
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

    public function getOrderType(): ?string
    {
        return $this->orderType;
    }

    public function setOrderType(?string $orderType): void
    {
        $this->orderType = $orderType;
    }
}
