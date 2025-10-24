<?php

namespace App\Entity;

use App\Repository\CritereRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CritereRepository::class)]
class Critere
{
    public const int TYPE_BATIMENT = 1;
    public const int TYPE_LOGEMENT = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $label = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Situation::class, inversedBy: 'criteres')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Situation $situation = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $modifiedAt = null;

    /**
     * @var Collection<int, Criticite>
     */
    #[ORM\OneToMany(mappedBy: 'critere', targetEntity: Criticite::class, orphanRemoval: true)]
    private Collection $criticites;

    #[ORM\Column(type: 'boolean')]
    private ?bool $isArchive = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $isDanger = null;

    #[ORM\Column(type: 'integer')]
    private ?int $coef = null;

    #[ORM\Column(type: 'integer')]
    private ?int $newCoef = null;

    #[ORM\Column(type: 'integer')]
    private ?int $type = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->criticites = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getSituation(): ?Situation
    {
        return $this->situation;
    }

    public function setSituation(?Situation $situation): self
    {
        $this->situation = $situation;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getModifiedAt(): ?\DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(?\DateTimeImmutable $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * @return Collection<int, Criticite>
     */
    public function getCriticites(): Collection
    {
        return $this->criticites;
    }

    public function addCriticite(Criticite $criticite): self
    {
        if (!$this->criticites->contains($criticite)) {
            $this->criticites[] = $criticite;
            $criticite->setCritere($this);
        }

        return $this;
    }

    public function removeCriticite(Criticite $criticite): self
    {
        if ($this->criticites->removeElement($criticite)) {
            // set the owning side to null (unless already changed)
            if ($criticite->getCritere() === $this) {
                $criticite->setCritere(null);
            }
        }

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

    public function getIsDanger(): ?bool
    {
        return $this->isDanger;
    }

    public function setIsDanger(bool $isDanger): self
    {
        $this->isDanger = $isDanger;

        return $this;
    }

    public function getCoef(): ?int
    {
        return $this->coef;
    }

    public function setCoef(int $coef): self
    {
        $this->coef = $coef;

        return $this;
    }

    public function getNewCoef(): ?int
    {
        return $this->newCoef;
    }

    public function setNewCoef(int $newCoef): self
    {
        $this->newCoef = $newCoef;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function getTypeString(): ?string
    {
        if (self::TYPE_BATIMENT == $this->type) {
            return 'batiment';
        } elseif (self::TYPE_LOGEMENT == $this->type) {
            return 'logement';
        }

        return null;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }
}
