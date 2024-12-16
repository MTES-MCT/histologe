<?php

namespace App\Service;

use App\Service\Behaviour\SearchQueryTrait;
use Symfony\Component\Validator\Constraints as Assert;

class SearchArchivedAccount
{
    use SearchQueryTrait {
        getUrlParams as getUrlParamsBase;
    }

    #[Assert\Positive(message: 'La page doit être un nombre positif')]
    private ?int $page = 1;
    private ?string $queryUser = null;
    private ?string $territory = null;
    private ?string $partner = null;

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

    public function getTerritory(): ?string
    {
        return $this->territory;
    }

    public function setTerritory(?string $territory): void
    {
        $this->territory = $territory;
    }

    public function getPartner(): ?string
    {
        return $this->partner;
    }

    public function setPartner(?string $partner): void
    {
        $this->partner = $partner;
    }

    public function getUrlParams(): array
    {
        return $this->getUrlParamsBase();
    }
}
