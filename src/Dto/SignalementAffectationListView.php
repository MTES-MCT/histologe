<?php

namespace App\Dto;

class SignalementAffectationListView
{
    private function __construct(
      private ?int $id = null,
      private ?int $uuid = null,
      private ?string $reference = null,
      private ?string $createdAt = null,
      private ?int $statut = null,
      private ?string $currentScore = null,
      private ?string $upcomingScore = null,
      private ?bool $isNotOccupant = null,
      private ?string $fullnameOccupant = null,
      private ?string $adresseOccupant = null,
      private ?string $cpOccupant = null,
      private ?string $villeOccupant = null,
      private ?array $affectations = null,
      private ?string $lastSuiviAt = null,
      private ?string $lastSuiviBy = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getUuid(): ?int
    {
        return $this->uuid;
    }

    public function setUuid(?int $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?string $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStatut(): ?int
    {
        return $this->statut;
    }

    public function setStatut(?int $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getCurrentScore(): ?string
    {
        return $this->currentScore;
    }

    public function setCurrentScore(?string $currentScore): self
    {
        $this->currentScore = $currentScore;

        return $this;
    }

    public function getUpcomingScore(): ?string
    {
        return $this->upcomingScore;
    }

    public function setUpcomingScore(?string $upcomingScore): self
    {
        $this->upcomingScore = $upcomingScore;

        return $this;
    }

    public function getIsNotOccupant(): ?bool
    {
        return $this->isNotOccupant;
    }

    public function setIsNotOccupant(?bool $isNotOccupant): self
    {
        $this->isNotOccupant = $isNotOccupant;

        return $this;
    }

    public function getFullnameOccupant(): ?string
    {
        return $this->fullnameOccupant;
    }

    public function setFullnameOccupant(?string $fullnameOccupant): self
    {
        $this->fullnameOccupant = $fullnameOccupant;

        return $this;
    }

    public function getAdresseOccupant(): ?string
    {
        return $this->adresseOccupant;
    }

    public function setAdresseOccupant(?string $adresseOccupant): self
    {
        $this->adresseOccupant = $adresseOccupant;

        return $this;
    }

    public function getCpOccupant(): ?string
    {
        return $this->cpOccupant;
    }

    public function setCpOccupant(?string $cpOccupant): self
    {
        $this->cpOccupant = $cpOccupant;

        return $this;
    }

    public function getVilleOccupant(): ?string
    {
        return $this->villeOccupant;
    }

    public function setVilleOccupant(?string $villeOccupant): self
    {
        $this->villeOccupant = $villeOccupant;

        return $this;
    }

    public function getAffectations(): ?array
    {
        return $this->affectations;
    }

    public function setAffectations(?array $affectations): self
    {
        $this->affectations = $affectations;

        return $this;
    }

    public function getLastSuiviAt(): ?string
    {
        return $this->lastSuiviAt;
    }

    public function setLastSuiviAt(?string $lastSuiviAt): self
    {
        $this->lastSuiviAt = $lastSuiviAt;

        return $this;
    }

    public function getLastSuiviBy(): ?string
    {
        return $this->lastSuiviBy;
    }

    public function setLastSuiviBy(?string $lastSuiviBy): self
    {
        $this->lastSuiviBy = $lastSuiviBy;

        return $this;
    }
}
