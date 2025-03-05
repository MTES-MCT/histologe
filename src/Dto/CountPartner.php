<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\Groups;

class CountPartner
{
    public function __construct(
        #[Groups(['widget:read'])]
        private readonly ?int $nonNotifiables = null,
    ) {
    }

    public function getNonNotifiables(): ?int
    {
        return $this->nonNotifiables;
    }
}
