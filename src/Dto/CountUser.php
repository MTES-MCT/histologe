<?php

namespace App\Dto;

class CountUser
{
    public function __construct(private int $active = 0, private int $inactive = 0)
    {
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
