<?php

namespace App\Dto;

class CountUser
{
    private ?array $percentage = [];

    public function __construct(
        private ?int $active = null,
        private ?int $inactive = null
    ) {
        $total = $this->active + $this->inactive;
        $this->percentage = [
            'active' => round($active / $total * 100, 1),
            'inactive' => round($inactive / $total * 100, 1),
        ];
    }

    public function getActive(): int
    {
        return $this->active;
    }

    public function getInactive(): int
    {
        return $this->inactive;
    }

    public function getPercentage(): ?array
    {
        return $this->percentage;
    }
}
