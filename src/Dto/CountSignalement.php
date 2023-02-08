<?php

namespace App\Dto;

class CountSignalement
{
    private ?array $percentage = null;
    private ?int $closedByAtLeastOnePartner = null;

    private ?int $affected = null;

    public function __construct(
        private ?int $total = 0,
        private ?int $new = null,
        private ?int $active = null,
        private ?int $closed = null,
        private ?int $refused = null,
    ) {
        $this->percentage = [
            'new' => 0 !== $total ? round($new / $total * 100, 1) : 0,
            'active' => 0 !== $total ? round($active / $total * 100, 1) : 0,
            'closed' => 0 !== $total ? round($closed / $total * 100, 1) : 0,
            'refused' => 0 !== $total ? round($refused / $total * 100, 1) : 0,
        ];
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getNew(): int
    {
        return $this->new;
    }

    public function getActive(): int
    {
        return $this->active;
    }

    public function getClosed(): int
    {
        return $this->closed;
    }

    public function getRefused(): int
    {
        return $this->refused;
    }

    public function getPercentage(): ?array
    {
        return $this->percentage;
    }

    public function getClosedByAtLeastOnePartner(): ?int
    {
        return $this->closedByAtLeastOnePartner;
    }

    public function setClosedByAtLeastOnePartner(?int $closedByAtLeastOnePartner): self
    {
        $this->closedByAtLeastOnePartner = $closedByAtLeastOnePartner;

        return $this;
    }

    public function getAffected(): ?int
    {
        return $this->affected;
    }

    public function setAffected(?int $affected): self
    {
        $this->affected = $affected;

        return $this;
    }
}
