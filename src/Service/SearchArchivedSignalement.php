<?php

namespace App\Service;

use App\Entity\Territory;
use App\Service\Behaviour\SearchQueryTrait;
use Symfony\Component\Validator\Constraints as Assert;

class SearchArchivedSignalement
{
    use SearchQueryTrait {
        getUrlParams as getUrlParamsBase;
    }

    #[Assert\Positive(message: 'La page doit être un nombre positif')]
    private ?int $page = 1;
    private ?string $queryReference = null;
    private ?Territory $territory = null;
    private ?string $orderType = null;

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

    public function getQueryReference(): ?string
    {
        return $this->queryReference;
    }

    public function setQueryReference(?string $queryReference): void
    {
        $this->queryReference = $queryReference;
    }

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): void
    {
        $this->territory = $territory;
    }

    public function getOrderType(): ?string
    {
        return $this->orderType;
    }

    public function setOrderType(?string $orderType): void
    {
        $this->orderType = $orderType;
    }

    public function getUrlParams(): array
    {
        return $this->getUrlParamsBase();
    }
}
