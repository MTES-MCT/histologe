<?php

namespace App\Service\ListFilters;

use App\Service\Behaviour\SearchQueryTrait;

class SearchInAppCommunication
{
    use SearchQueryTrait;

    private ?string $queryTitleOrDescription = null;

    private ?string $communicationType = null;

    public function getQueryTitleOrDescription(): ?string
    {
        return $this->queryTitleOrDescription;
    }

    public function setQueryTitleOrDescription(?string $queryTitleOrDescription): void
    {
        $this->queryTitleOrDescription = $queryTitleOrDescription;
    }

    public function getCommunicationType(): ?string
    {
        return $this->communicationType;
    }

    public function setCommunicationType(?string $communicationType): void
    {
        $this->communicationType = $communicationType;
    }

    private ?string $orderType = null;

    public function getOrderType(): ?string
    {
        return $this->orderType;
    }

    public function setOrderType(?string $orderType): void
    {
        $this->orderType = $orderType;
    }
}
