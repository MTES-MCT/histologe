<?php

namespace App\Service;

use App\Entity\Enum\PartnerType;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\Behaviour\SearchQueryTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class SearchUser
{
    use SearchQueryTrait {
        getUrlParams as getUrlParamsBase;
    }

    private User $user;
    #[Assert\Positive(message: 'La page doit être un nombre positif')]
    private ?int $page = 1;
    private ?string $queryUser = null;
    private ?Territory $territory = null;
    private Collection $partners;
    private ?PartnerType $partnerType = null;
    private ?int $statut = null;
    private ?string $role = null;
    private ?string $permissionAffectation = null;

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

    public function getPage(): int
    {
        if ($this->page < 1) {
            return 1;
        }

        return $this->page;
    }

    public function setPage(?int $page): void
    {
        $this->page = $page;
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

    public function getPartners(): Collection
    {
        return $this->partners;
    }

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

    public function getStatut(): ?int
    {
        return $this->statut;
    }

    public function setStatut(?int $statut): void
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
        if ($this->queryUser) {
            $filters['Recherche'] = $this->queryUser;
        }
        if ($this->territory && $this->user->isSuperAdmin()) {
            $filters['Territoire'] = $this->territory->getZip().' - '.$this->territory->getName();
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
            $filters['Statut'] = User::STATUS_LABELS[$this->statut];
        }
        if ($this->role) {
            $filters['Rôle'] = array_search($this->role, User::ROLES);
        }
        if ($this->permissionAffectation) {
            $filters['Droits d\'affectation'] = $this->permissionAffectation;
        }

        return $filters;
    }
}
