<?php

namespace App\Service\ListFilters;

use App\Entity\User;
use App\Service\Behaviour\SearchQueryTrait;

class SearchNotification
{
    use SearchQueryTrait;

    private User $user;
    private ?string $orderType = null;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getOrderType(): ?string
    {
        return $this->orderType;
    }

    public function setOrderType(?string $orderType): void
    {
        $this->orderType = $orderType;
    }
}
