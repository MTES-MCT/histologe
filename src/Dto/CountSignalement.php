<?php

namespace App\Dto;

class CountSignalement
{
    public function __construct(
        private ?int $total = null,
        private ?int $new = null,
        private ?int $active = null,
        private ?int $closed = null,
        private ?int $refused = null
    ) {
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
}
