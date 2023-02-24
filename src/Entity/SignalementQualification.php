<?php

namespace App\Entity;

use App\Repository\SignalementQualificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SignalementQualificationRepository::class)]
class SignalementQualification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'signalementQualifications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Signalement $signalement = null;

    #[ORM\Column(length: 255)]
    private ?string $qualification = null;

    #[ORM\Column(nullable: true)]
    private array $desordres = [];

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDernierBail = null;

    #[ORM\Column(nullable: true)]
    private array $details = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSignalement(): ?Signalement
    {
        return $this->signalement;
    }

    public function setSignalement(?Signalement $signalement): self
    {
        $this->signalement = $signalement;

        return $this;
    }

    public function getQualification(): ?string
    {
        return $this->qualification;
    }

    public function setQualification(string $qualification): self
    {
        $this->qualification = $qualification;

        return $this;
    }

    public function getDesordres(): array
    {
        return $this->desordres;
    }

    public function setDesordres(?array $desordres): self
    {
        $this->desordres = $desordres;

        return $this;
    }

    public function getDateDernierBail(): ?\DateTimeInterface
    {
        return $this->dateDernierBail;
    }

    public function setDateDernierBail(?\DateTimeInterface $dateDernierBail): self
    {
        $this->dateDernierBail = $dateDernierBail;

        return $this;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function setDetails(?array $details): self
    {
        $this->details = $details;

        return $this;
    }
}
