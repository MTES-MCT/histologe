<?php

namespace App\Dto;

class CountPartner
{
    public function __construct(
        private readonly ?int $nonNotifiables = null,
    ) {
    }

    public function getNonNotifiables(): ?int
    {
        return $this->nonNotifiables;
    }
}
