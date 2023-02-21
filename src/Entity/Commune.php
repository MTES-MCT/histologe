<?php

namespace App\Entity;

use App\Repository\CommuneRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommuneRepository::class)]
class Commune
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Territory::class, inversedBy: 'communes')]
    private $territory;

    #[ORM\Column(type: 'string', length: 255)]
    private $nom;

    #[ORM\Column(type: 'string', length: 10)]
    private $codePostal;

    #[ORM\Column(type: 'string', length: 10)]
    private $codeInsee;

    #[ORM\Column(type: 'boolean')]
    private $isZonePermisLouer;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(string $codePostal): self
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getCodeInsee(): ?string
    {
        return $this->codeInsee;
    }

    public function setCodeInsee(string $codeInsee): self
    {
        $this->codeInsee = $codeInsee;

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

    public function __toString(): string
    {
        return $this->nom;
    }

    /**
     * Get the value of isZonePermisLouer.
     */
    public function getIsZonePermisLouer(): ?bool
    {
        return $this->isZonePermisLouer;
    }

    /**
     * Set the value of isZonePermisLouer.
     */
    public function setIsZonePermisLouer(?bool $isZonePermisLouer): self
    {
        $this->isZonePermisLouer = $isZonePermisLouer;

        return $this;
    }
}
