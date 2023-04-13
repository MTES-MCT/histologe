<?php

namespace App\Dto\Request\Signalement;

class VisiteRequest
{
    public function __construct(
        private ?int $idIntervention = null,
        private ?string $date = null,
        private ?int $idPartner = null,
        private ?string $details = null,
    ) {
    }

    public function getIntervention(): ?string
    {
        return $this->idIntervention;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function getPartner(): ?int
    {
        return $this->idPartner;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }
}
