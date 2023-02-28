<?php

namespace App\Entity;

use App\Repository\CriticiteRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CriticiteRepository::class)]
class Criticite
{
    public const SCORE_MAX = 3;
    public const ETAT_MOYEN = 'moyen';
    public const ETAT_GRAVE = 'grave';
    public const ETAT_TRES_GRAVE = 'très grave';

    public const ETAT_LABEL = [
        'etat moyen' => 1,
        'mauvais état' => 2,
        'très mauvais état' => 3,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'text')]
    private $label;

    #[ORM\ManyToOne(targetEntity: Critere::class, inversedBy: 'criticites')]
    #[ORM\JoinColumn(nullable: false)]
    private $critere;

    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $modifiedAt;

    #[ORM\ManyToMany(targetEntity: Signalement::class, mappedBy: 'criticites')]
    private $signalements;

    #[ORM\Column(type: 'integer')]
    private $score;

    #[ORM\Column(type: 'float')]
    private $newScore;

    #[ORM\Column(type: 'boolean')]
    private $isDanger;

    #[ORM\Column(type: 'boolean')]
    private $isArchive;

    #[ORM\Column(type: 'boolean')]
    private $isDefault;

    #[ORM\Column(type: 'json', nullable: true)]
    private $qualification = [];

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->signalements = new ArrayCollection();
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

    public function getCritere(): ?Critere
    {
        return $this->critere;
    }

    public function setCritere(?Critere $critere): self
    {
        $this->critere = $critere;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getModifiedAt(): ?DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(?DateTimeImmutable $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * @return Collection|Signalement[]
     */
    public function getSignalements(): Collection
    {
        return $this->signalements;
    }

    public function addSignalement(Signalement $signalement): self
    {
        if (!$this->signalements->contains($signalement)) {
            $this->signalements[] = $signalement;
            $signalement->addCriticite($this);
        }

        return $this;
    }

    public function removeSignalement(Signalement $signalement): self
    {
        if ($this->signalements->removeElement($signalement)) {
            $signalement->removeCriticite($this);
        }

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getNewScore(): ?float
    {
        return $this->newScore;
    }

    public function setNewScore(float $newScore): self
    {
        $this->newScore = $newScore;

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

    public function getIsArchive(): ?bool
    {
        return $this->isArchive;
    }

    public function setIsArchive(bool $isArchive): self
    {
        $this->isArchive = $isArchive;

        return $this;
    }

    public function getIsDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function getScoreLabel(): string
    {
        // TODO à revoir avec le nouvel algo de criticité (les scores ne sont plus forcément 1, 2, 3)
        return match ($this->score) {
            1 => self::ETAT_MOYEN,
            2 => self::ETAT_GRAVE,
            3 => self::ETAT_TRES_GRAVE,
        };
    }

    public function getQualification(): ?array
    {
        return $this->qualification;
    }

    public function setQualification(?array $qualification): self
    {
        $this->qualification = $qualification;

        return $this;
    }
}
