<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;

class VisiteRequest
{
    /**
     * @throws \Exception
     */
    public function __construct(
        private readonly ?int $idIntervention = null,
        #[Assert\DateTime('Y-m-d')]
        private readonly ?string $date = null,
        #[Assert\DateTime('H:i')]
        private readonly ?string $time = null,
        private readonly ?string $timezone = 'Europe/Paris',
        private readonly ?int $idPartner = null,
        private readonly ?string $details = null,
        private readonly ?array $concludeProcedure = [],
        private readonly ?bool $isVisiteDone = null,
        private readonly ?bool $isOccupantPresent = null,
        private readonly ?bool $isProprietairePresent = null,
        private readonly ?bool $isUsagerNotified = null,
        private readonly ?string $document = null,
    ) {
    }

    public function getIntervention(): ?int
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

    public function getDateTimeLocale(): ?string
    {
        if ($this->getTime()) {
            return $this->getDate().' '.$this->getTime();
        }

        return $this->getDate();
    }

    /**
     * @throws \Exception
     */
    public function getDateTimeUTC(): ?string
    {
        if ($this->getTime()) {
            $localDateTime = new \DateTimeImmutable(
                $this->getDate().' '.$this->getTime(),
                new \DateTimeZone($this->getTimezone())
            );

            return $localDateTime->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
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

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }
}
