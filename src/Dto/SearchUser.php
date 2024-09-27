<?php

namespace App\Dto;

use App\Entity\Territory;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class SearchUser
{
    private User $user;
    #[Assert\Positive(message: 'La page doit être un nombre positif')]
    private int $page = 1;
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
        return $this->page;
    }

    public function setPage(int $page): void
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

    public function getQueryStringForUrl(): array
    {
        $params = [];
        foreach (get_object_vars($this) as $key => $value) {
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
        unset($params['user']);
        unset($params['page']);

        return $params;
    }
}
