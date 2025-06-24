<?php

namespace App\Dto;

use App\Entity\Enum\MotifRefus;
use App\Entity\File;
use App\Entity\Signalement;
use Symfony\Component\Validator\Constraints as Assert;

class RefusSignalement
{
    private ?Signalement $signalement = null;

    #[Assert\NotBlank()]
    private ?MotifRefus $motifRefus = null;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 10, minMessage: 'Le message doit contenir au moins {{ limit }} caractères.')]
    private ?string $description = null;

    /** @var array<File> */
    private array $files = [];

    public function getSignalement(): ?Signalement
    {
        return $this->signalement;
    }

    public function setSignalement(Signalement $signalement): self
    {
        $this->signalement = $signalement;

        return $this;
    }

    public function getMotifRefus(): ?MotifRefus
    {
        return $this->motifRefus;
    }

    public function setMotifRefus(MotifRefus $motifRefus): self
    {
        $this->motifRefus = $motifRefus;

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

    /** @return array<File> */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @param array<File> $files
     */
    public function setFiles(array $files): self
    {
        $this->files = $files;

        return $this;
    }
}
