<?php

namespace App\Entity;

use App\Repository\DuplicateAddresseDetectionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DuplicateAddresseDetectionRepository::class)]
class DuplicateAddresseDetection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 5)]
    private ?string $zip = null;

    #[ORM\Column(length: 100)]
    private ?string $city = null;

    #[ORM\Column]
    private ?int $nbDuplication = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Territory $territory = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(string $zip): static
    {
        $this->zip = $zip;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getNbDuplication(): ?int
    {
        return $this->nbDuplication;
    }

    public function setNbDuplication(int $nbDuplication): static
    {
        $this->nbDuplication = $nbDuplication;

        return $this;
    }

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): static
    {
        $this->territory = $territory;

        return $this;
    }
}
