<?php

namespace App\Entity;

use App\Repository\CommuneRepository;
use App\Utils\ImportCommune;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[ORM\Entity(repositoryClass: CommuneRepository::class)]
#[UniqueConstraint(name: 'code_postal_code_insee_unique', columns: ['code_postal', 'code_insee'])]
class Commune
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Territory::class, inversedBy: 'communes')]
    private ?Territory $territory = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 10)]
    private ?string $codePostal = null;

    #[ORM\Column(type: 'string', length: 10)]
    private ?string $codeInsee = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $isZonePermisLouer = null;

    #[ORM\ManyToOne(inversedBy: 'communes', cascade: ['persist'])]
    private ?Epci $epci = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(bool $withArrondissement = false): ?string
    {
        if ($withArrondissement) {
            return $this->nom;
        }

        return ImportCommune::sanitizeCommuneWithArrondissement($this->nom);
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

    public function getEpci(): ?Epci
    {
        return $this->epci;
    }

    public function setEpci(?Epci $epci): static
    {
        $this->epci = $epci;

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom;
    }

    public function getIsZonePermisLouer(): ?bool
    {
        return $this->isZonePermisLouer;
    }

    public function setIsZonePermisLouer(?bool $isZonePermisLouer): self
    {
        $this->isZonePermisLouer = $isZonePermisLouer;

        return $this;
    }
}
