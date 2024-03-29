<?php

namespace App\Entity;

use App\Repository\SuiviRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SuiviRepository::class)]
class Suivi
{
    public const TYPE_AUTO = 1;
    public const TYPE_USAGER = 2;
    public const TYPE_PARTNER = 3;
    public const TYPE_TECHNICAL = 4;

    public const CONTEXT_INTERVENTION = 'intervention';

    public const DEFAULT_PERIOD_INACTIVITY = 30;
    public const DEFAULT_PERIOD_RELANCE = 45;

    public const DESCRIPTION_MOTIF_CLOTURE_ALL = 'Le signalement a été cloturé pour tous';
    public const DESCRIPTION_MOTIF_CLOTURE_PARTNER = 'Le signalement a été cloturé pour';
    public const DESCRIPTION_SIGNALEMENT_VALIDE = 'Signalement validé';

    public const ARRET_PROCEDURE = 'arret-procedure';
    public const POURSUIVRE_PROCEDURE = 'poursuivre-procedure';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'suivis')]
    #[ORM\JoinColumn(nullable: true)]
    private $createdBy;

    #[ORM\Column(type: 'text')]
    private $description;

    #[ORM\Column(type: 'boolean')]
    private $isPublic;

    #[ORM\Column(type: 'integer')]
    private $type;

    #[ORM\ManyToOne(targetEntity: Signalement::class, inversedBy: 'suivis')]
    #[ORM\JoinColumn(nullable: false)]
    private $signalement;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $context = null;

    private bool $sendMail = true;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->isPublic = false;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

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

    public function getIsPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;

        return $this;
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

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setContext(?string $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function getSendMail(): bool
    {
        return $this->sendMail;
    }

    public function setSendMail(bool $sendMail): self
    {
        $this->sendMail = $sendMail;

        return $this;
    }
}
