<?php

namespace App\Dto\Request\Signalement;

class VisiteRequest
{
    public function __construct(
        private ?string $date = null,
        private ?int $idPartner = null,
    ) {
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function getPartner(): ?int
    {
        return $this->idPartner;
    }
}
