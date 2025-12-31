<?php

namespace App\Service\ListFilters;

use App\Entity\Enum\PartnerType;
use App\Entity\Enum\UserStatus;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\Behaviour\SearchQueryTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class SearchUser
{
    use SearchQueryTrait {
        getUrlParams as getUrlParamsBase;
    }

    private User $user;
    private ?string $queryUser = null;
    private ?Territory $territory = null;
    /** @var Collection<int, Partner> */
    private Collection $partners;
    private ?PartnerType $partnerType = null;
    private ?string $statut = null;
    private ?string $role = null;
    private ?string $permissionAffectation = null;
    private ?string $emailDeliveryIssue = null;
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

    public function getPartnerType(): ?PartnerType
    {
        return $this->partnerType;
    }

    public function setPartnerType(PartnerType $partnerType): void
    {
        $this->partnerType = $partnerType;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): void
    {
        $this->statut = $statut;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): void
    {
        $this->role = $role;
    }

    public function getPermissionAffectation(): ?string
    {
        return $this->permissionAffectation;
    }

    public function setPermissionAffectation(?string $permissionAffectation): void
    {
        $this->permissionAffectation = $permissionAffectation;
    }

    public function getEmailDeliveryIssue(): ?string
    {
        return $this->emailDeliveryIssue;
    }

    public function setEmailDeliveryIssue(?string $emailDeliveryIssue): void
    {
        $this->emailDeliveryIssue = $emailDeliveryIssue;
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
        if ($this->queryUser) {
            $filters['Recherche'] = $this->queryUser;
        }
        if ($this->territory && $this->user->isSuperAdmin()) {
            $filters['Territoire'] = $this->territory->getZipAndName();
        }
        if ($this->partners->count()) {
            $label = '';
            foreach ($this->partners as $partner) {
                $label .= $partner->getNom().', ';
            }
            $label = substr($label, 0, -2);
            $filters['Partenaires'] = $label;
        }
        if (null !== $this->partnerType) {
            $filters['Type de partenaire'] = $this->partnerType->label();
        }
        if (null !== $this->statut) {
            $filters['Statut'] = UserStatus::from($this->statut)->label();
        }
        if ($this->role) {
            $filters['Rôle'] = array_search($this->role, User::ROLES);
        }
        if ($this->permissionAffectation) {
            $filters['Droits d\'affectation'] = $this->permissionAffectation;
        }
        if ($this->emailDeliveryIssue) {
            $filters['Problème d\'adresse e-mail'] = $this->emailDeliveryIssue;
        }

        return $filters;
    }
}
