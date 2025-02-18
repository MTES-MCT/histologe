<?php

namespace App\Service\ListFilters;

use App\Entity\User;
use App\Service\Behaviour\SearchQueryTrait;

class SearchDraft
{
    use SearchQueryTrait {
        getUrlParams as getUrlParamsBase;
    }

    private User $user;
    private ?string $queryDraft = null;
    private ?string $orderType = null;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getQueryDraft(): ?string
    {
        return $this->queryDraft;
    }

    public function setQueryDraft(?string $queryDraft): void
    {
        $this->queryDraft = $queryDraft;
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
        $params = $this->getUrlParamsBase();

        return $params;
    }

    public function getFiltersToText(): array
    {
        $filters = [];
        if ($this->queryDraft) {
            $filters['Recherche'] = $this->queryDraft;
        }

        return $filters;
    }
}
