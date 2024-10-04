<?php

namespace App\Service;

use App\Entity\Territory;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class SearchUser
{
    private User $user;
    #[Assert\Positive(message: 'La page doit être un nombre positif')]
    private ?int $page = 1;
    private ?string $queryUser = null;
    private ?Territory $territory = null;
    private Collection $partners;
    private ?int $statut = null;
    private ?string $role = null;

    public function __construct(User $user)
    {
        $this->user = $user;
        if (!$user->isSuperAdmin()) {
            $this->territory = $user->getTerritory();
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

    public function getUrlParams(): array
    {
        $params = [];
        foreach (get_object_vars($this) as $key => $value) {
            if (in_array($key, ['user', 'page'])) {
                continue;
            }
            if ($value instanceof Collection) {
                if ($value->isEmpty()) {
                    continue;
                }
                $params[$key] = $value->map(fn ($partner) => $partner->getId())->toArray();
            } elseif (is_object($value)) {
                $params[$key] = $value->getId();
            } elseif (null !== $value) {
                $params[$key] = $value;
            }
        }
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
        if (null !== $this->statut) {
            $filters['Statut'] = User::STATUS_LABELS[$this->statut];
        }
        if ($this->role) {
            $filters['Rôle'] = array_search($this->role, User::ROLESV2);
        }

        return $filters;
    }
}
