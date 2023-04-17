<?php

namespace App\Dto\Request\Signalement;

class VisiteRequest
{
    public function __construct(
        private ?int $idIntervention = null,
        private ?string $date = null,
        private ?int $idPartner = null,
        private ?string $details = null,
        private ?string $concludeProcedure = null,
        private ?bool $isVisiteDone = null,
        private ?bool $isOccupantPresent = null,
        private ?bool $isUsagerNotified = null,
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

    public function getConcludeProcedure(): ?string
    {
        return $this->concludeProcedure;
    }

    public function isVisiteDone(): ?bool
    {
        return $this->isVisiteDone;
    }

    public function isOccupantPresent(): ?bool
    {
        return $this->isOccupantPresent;
    }

    public function isUsagerNotified(): ?bool
    {
        return $this->isUsagerNotified;
    }
}
