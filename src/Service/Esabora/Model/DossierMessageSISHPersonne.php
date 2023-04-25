<?php

namespace App\Service\Esabora\Model;

class DossierMessageSISHPersonne
{
    private ?string $type = null;
    private ?string $nom = null;
    private ?string $prenom = null;
    private ?string $telephone = null;
    private ?string $email = null;
    private ?string $lienOccupant = null;
    private ?string $structure = null;
    private ?string $adresse = null;
    private ?string $representant = null;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getLienOccupant(): ?string
    {
        return $this->lienOccupant;
    }

    public function setLienOccupant(?string $lienOccupant): self
    {
        $this->lienOccupant = $lienOccupant;

        return $this;
    }

    public function getStructure(): ?string
    {
        return $this->structure;
    }

    public function setStructure(?string $structure): self
    {
        $this->structure = $structure;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getRepresentant(): ?string
    {
        return $this->representant;
    }

    public function setRepresentant(?string $representant): self
    {
        $this->representant = $representant;

        return $this;
    }
}
