<?php

namespace App\Service;

use App\Entity\Territory;
use App\Service\Behaviour\SearchQueryTrait;
use Symfony\Component\Validator\Constraints as Assert;

class SearchAutoAffectationRule
{
    use SearchQueryTrait {
        getUrlParams as getUrlParamsBase;
    }

    #[Assert\Positive(message: 'La page doit être un nombre positif')]
    private ?int $page = 1;
    private ?Territory $territory = null;

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
        return $this->getUrlParamsBase();
    }
}
