<?php

namespace App\Service\ListFilters;

use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\User;
use App\Service\Behaviour\SearchQueryTrait;

class SearchClubEvent
{
    use SearchQueryTrait;

    private User $user;
    private ?bool $isInFuture = null;
    private ?string $queryName = null;
    private ?PartnerType $partnerType = null;
    private ?Qualification $partnerCompetence = null;
    private ?string $orderType = null;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getIsInFuture(): ?bool
    {
        return $this->isInFuture;
    }

    public function setIsInFuture(?bool $isInFuture): void
    {
        $this->isInFuture = $isInFuture;
    }

    public function getQueryName(): ?string
    {
        return $this->queryName;
    }

    public function setQueryName(?string $queryName): void
    {
        $this->queryName = $queryName;
    }

    public function getPartnerType(): ?PartnerType
    {
        return $this->partnerType;
    }

    public function setPartnerType(PartnerType $partnerType): void
    {
        $this->partnerType = $partnerType;
    }

    public function getPartnerCompetence(): ?Qualification
    {
        return $this->partnerCompetence;
    }

    public function setPartnerCompetence(Qualification $partnerCompetence): void
    {
        $this->partnerCompetence = $partnerCompetence;
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
