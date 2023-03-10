<?php

namespace App\Dto;

class SignalementAffectationListView
{
    public function __construct(
      private ?int $id = null,
      private ?string $uuid = null,
      private ?string $reference = null,
      private ?\DateTimeImmutable $createdAt = null,
      private ?int $statut = null,
      private ?string $scoreCreation = null,
      private ?string $newScoreCreation = null,
      private ?bool $isNotOccupant = null,
      private ?string $nomOccupant = null,
      private ?string $prenomOccupant = null,
      private ?string $adresseOccupant = null,
      private ?string $villeOccupant = null,
      private ?\DateTimeImmutable $lastSuiviAt = null,
      private ?array $affectations = null,
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

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid): self
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): self
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

    public function getScoreCreation(): ?string
    {
        return $this->scoreCreation;
    }

    public function setScoreCreation(?string $scoreCreation): self
    {
        $this->scoreCreation = $scoreCreation;

        return $this;
    }

    public function getNewScoreCreation(): ?string
    {
        return $this->newScoreCreation;
    }

    public function setNewScoreCreation(?string $newScoreCreation): self
    {
        $this->newScoreCreation = $newScoreCreation;

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

    public function getNomOccupant(): ?string
    {
        return $this->nomOccupant;
    }

    public function setNomOccupant(?string $nomOccupant): self
    {
        $this->nomOccupant = $nomOccupant;

        return $this;
    }

    public function getPrenomOccupant(): ?string
    {
        return $this->prenomOccupant;
    }

    public function setPrenomOccupant(?string $prenomOccupant): self
    {
        $this->prenomOccupant = $prenomOccupant;

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

    public function getVilleOccupant(): ?string
    {
        return $this->villeOccupant;
    }

    public function setVilleOccupant(?string $villeOccupant): self
    {
        $this->villeOccupant = $villeOccupant;

        return $this;
    }

    public function getLastSuiviAt(): ?\DateTimeImmutable
    {
        return $this->lastSuiviAt;
    }

    public function setLastSuiviAt(?\DateTimeImmutable $lastSuiviAt): self
    {
        $this->lastSuiviAt = $lastSuiviAt;

        return $this;
    }

    public function getAffectations(): array
    {
        return $this->affectations;
    }

    public function setAffectations(array $affectations): self
    {
        $this->affectations = $affectations;

        return $this;
    }
}
