<?php

namespace App\Dto\Request\Signalement;

class VisiteRequest
{
    public function __construct(
        private ?int $idIntervention = null,
        private ?string $date = null,
        private ?string $time = null,
        private ?int $idPartner = null,
        private ?string $details = null,
        private ?array $concludeProcedure = [],
        private ?bool $isVisiteDone = null,
        private ?bool $isOccupantPresent = null,
        private ?bool $isProprietairePresent = null,
        private ?bool $isUsagerNotified = null,
        private ?string $document = null,
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

    public function getTime(): ?string
    {
        return $this->time;
    }

    public function getDateTime(): ?string
    {
        if ($this->getTime()) {
            return $this->getDate().' '.$this->getTime();
        }

        return $this->getDate();
    }

    public function getPartner(): ?int
    {
        return $this->idPartner;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function getConcludeProcedure(): ?array
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

    public function isProprietairePresent(): ?bool
    {
        return $this->isProprietairePresent;
    }

    public function isUsagerNotified(): ?bool
    {
        return $this->isUsagerNotified;
    }

    public function getDocument(): ?string
    {
        return $this->document;
    }
}
