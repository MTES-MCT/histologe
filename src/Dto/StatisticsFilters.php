<?php

namespace App\Dto;

use App\Entity\Territory;
use DateTime;

class StatisticsFilters
{
    private array $communes;
    private string $statut;
    private array $etiquettes;
    private string $type;
    private DateTime $dateStart;
    private DateTime $dateEnd;
    private bool $countRefused;
    private bool $countArchived;
    private ?Territory $territory;

    public function __construct(
        array $communes,
        string $statut,
        array $etiquettes,
        string $type,
        DateTime $dateStart,
        DateTime $dateEnd,
        bool $countRefused,
        bool $countArchived,
        ?Territory $territory,
        ) {
        $this->communes = $communes;
        $this->statut = $statut;
        $this->etiquettes = $etiquettes;
        $this->type = $type;
        $this->dateStart = $dateStart;
        $this->dateEnd = $dateEnd;
        $this->countRefused = $countRefused;
        $this->countArchived = $countArchived;
        $this->territory = $territory;
    }

    public function getCommunes(): ?array
    {
        return $this->communes;
    }

    public function setCommunes(array $communes): self
    {
        $this->communes = $communes;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getEtiquettes(): ?array
    {
        return $this->etiquettes;
    }

    public function setEtiquettes(array $etiquettes): self
    {
        $this->etiquettes = $etiquettes;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getDateStart(): ?DateTime
    {
        return $this->dateStart;
    }

    public function setDateStart(DateTime $dateStart): self
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    public function getDateEnd(): ?DateTime
    {
        return $this->dateEnd;
    }

    public function setDateEnd(DateTime $dateEnd): self
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    public function isCountRefused(): ?bool
    {
        return $this->countRefused;
    }

    public function setCountRefused(?bool $countRefused): self
    {
        $this->countRefused = $countRefused;

        return $this;
    }

    public function isCountArchived(): ?bool
    {
        return $this->countArchived;
    }

    public function setCountArchived(?bool $countArchived): self
    {
        $this->countArchived = $countArchived;

        return $this;
    }

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): self
    {
        $this->territory = $territory;

        return $this;
    }
}
