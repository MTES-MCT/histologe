<?php

namespace App\Service;

use App\Entity\Territory;
use App\Entity\User;
use App\Service\Behaviour\SearchQueryTrait;
use Symfony\Component\Validator\Constraints as Assert;

class SearchZone
{
    use SearchQueryTrait {
        getUrlParams as getUrlParamsBase;
    }
    private User $user;
    #[Assert\Positive(message: 'La page doit être un nombre positif')]
    private ?int $page = 1;
    private ?string $queryName = null;
    private ?Territory $territory = null;

    public function __construct(User $user)
    {
        $this->user = $user;
        if (!$user->isSuperAdmin()) {
            $this->territory = $user->getTerritory();
        }
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

    public function getUrlParams(): array
    {
        $params = $this->getUrlParamsBase();
        if (isset($params['territory']) && !$this->getUser()->isSuperAdmin()) {
            unset($params['territory']);
        }

        return $params;
    }
}
