<?php

namespace App\Dto;

use App\Entity\Enum\MotifCloture;
use App\Entity\Signalement;
use Symfony\Component\Validator\Constraints as Assert;

class SignalementAffectationClose
{
    private ?Signalement $signalement = null;

    #[Assert\NotBlank()]
    private ?MotifCloture $motifCloture = null;

    #[Assert\NotBlank()]
    private ?string $type = null;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 10, minMessage: 'Le contenu doit contenir au moins {{ limit }} caractères.')]
    private ?string $description = null;

    private array $files = [];

    private bool $isPublic = true;

    private ?string $subject = null;

    public function getSignalement(): ?Signalement
    {
        return $this->signalement;
    }

    public function setSignalement(Signalement $signalement): self
    {
        $this->signalement = $signalement;

        return $this;
    }

    public function getMotifCloture(): ?MotifCloture
    {
        return $this->motifCloture;
    }

    public function setMotifCloture(MotifCloture $motifCloture): self
    {
        $this->motifCloture = $motifCloture;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

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

    public function getFiles(): array
    {
        return $this->files;
    }

    public function setFiles(array $files): self
    {
        $this->files = $files;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }
}
