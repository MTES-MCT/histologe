<?php

namespace App\Dto;

class CountSignalement
{
    public function __construct(
        private int $total = 0,
        private int $new = 0,
        private int $active = 0,
        private int $closed = 0,
        private int $refused = 0
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
