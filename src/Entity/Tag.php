<?php

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TagRepository::class)]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToMany(targetEntity: Signalement::class, inversedBy: 'tags', cascade: ['persist'])]
    private $signalement;

    #[ORM\Column(type: 'string', length: 255)]
    private $label;

    #[ORM\Column(type: 'boolean')]
    private $isArchive;

    #[ORM\ManyToOne(targetEntity: Territory::class, inversedBy: 'tags')]
    #[ORM\JoinColumn(nullable: false)]
    private $territory;

    public function __construct()
    {
        $this->signalement = new ArrayCollection();
        $this->isArchive = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|Signalement[]
     */
    public function getSignalement(): Collection
    {
        return $this->signalement;
    }

    public function addSignalement(Signalement $signalement): self
    {
        if (!$this->signalement->contains($signalement)) {
            $this->signalement[] = $signalement;
        }

        return $this;
    }

    public function removeSignalement(Signalement $signalement): self
    {
        $this->signalement->removeElement($signalement);

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getIsArchive(): ?bool
    {
        return $this->isArchive;
    }

    public function setIsArchive(bool $isArchive): self
    {
        $this->isArchive = $isArchive;

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
