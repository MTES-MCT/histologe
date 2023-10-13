<?php

namespace App\Entity;

use App\Repository\DesordrePrecisionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DesordrePrecisionRepository::class)]
class DesordrePrecision
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $coef = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isDanger = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $label = null;

    #[ORM\Column]
    private array $qualification = [];

    #[ORM\ManyToOne(inversedBy: 'desordrePrecisions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DesordreCritere $desordreCritere = null;

    #[ORM\Column(length: 255)]
    private ?string $desordrePrecisionSlug = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $modifiedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCoef(): ?int
    {
        return $this->coef;
    }

    public function setCoef(int $coef): static
    {
        $this->coef = $coef;

        return $this;
    }

    public function isIsDanger(): ?bool
    {
        return $this->isDanger;
    }

    public function setIsDanger(?bool $isDanger): static
    {
        $this->isDanger = $isDanger;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getQualification(): array
    {
        return $this->qualification;
    }

    public function setQualification(array $qualification): static
    {
        $this->qualification = $qualification;

        return $this;
    }

    public function getDesordreCritere(): ?DesordreCritere
    {
        return $this->desordreCritere;
    }

    public function setDesordreCritere(?DesordreCritere $desordreCritere): static
    {
        $this->desordreCritere = $desordreCritere;

        return $this;
    }

    public function getDesordrePrecisionSlug(): ?string
    {
        return $this->desordrePrecisionSlug;
    }

    public function setDesordrePrecisionSlug(string $desordrePrecisionSlug): static
    {
        $this->desordrePrecisionSlug = $desordrePrecisionSlug;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getModifiedAt(): ?\DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(\DateTimeImmutable $modifiedAt): static
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }
}
