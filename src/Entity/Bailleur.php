<?php

namespace App\Entity;

use App\Repository\BailleurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: BailleurRepository::class)]
class Bailleur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Ignore]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    #[Ignore]
    private ?bool $isSocial = null;

    #[ORM\Column]
    #[Ignore]
    private ?bool $active = true;

    #[ORM\ManyToOne(inversedBy: 'bailleurs')]
    #[ORM\JoinColumn(nullable: false)]
    #[Ignore]
    private ?Territory $territory = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isIsSocial(): ?bool
    {
        return $this->isSocial;
    }

    public function setIsSocial(bool $isSocial): static
    {
        $this->isSocial = $isSocial;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

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
