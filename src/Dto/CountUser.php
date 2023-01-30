<?php

namespace App\Dto;

class CountUser
{
    public function __construct(
        private ?int $active = null,
        private ?int $inactive = null
    ) {
    }

    public function getActive(): int
    {
        return $this->active;
    }

    public function getInactive(): int
    {
        return $this->inactive;
    }
}
