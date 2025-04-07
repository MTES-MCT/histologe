<?php

namespace App\Entity;

use App\Entity\Enum\NotificationType;
use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    public const MAX_LIST_PAGINATION = 100;
    public const string EXPIRATION_PERIOD = '- 30 days';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user;

    #[ORM\Column(type: 'boolean')]
    private ?bool $isSeen;

    #[ORM\Column(
        type: 'string',
        enumType: NotificationType::class,
        options: ['comment' => 'Value possible enum NotificationType'])]
    private NotificationType $type;

    #[ORM\ManyToOne(targetEntity: Signalement::class)]
    private ?Signalement $signalement;

    #[ORM\ManyToOne(targetEntity: Suivi::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Suivi $suivi;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: Affectation::class, inversedBy: 'notifications')]
    private $affectation;

    #[ORM\Column]
    private bool $waitMaillingSummary;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $maillingSummarySentAt = null;

    #[ORM\Column]
    private bool $deleted;

    public function __construct()
    {
        $this->isSeen = false;
        $this->deleted = false;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getIsSeen(): ?bool
    {
        return $this->isSeen;
    }

    public function setIsSeen(bool $isSeen): self
    {
        $this->isSeen = $isSeen;

        return $this;
    }

    public function getType(): NotificationType
    {
        return $this->type;
    }

    public function setType(NotificationType $type): self
    {
        $this->type = $type;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getSuivi(): ?Suivi
    {
        return $this->suivi;
    }

    public function setSuivi(?Suivi $suivi): self
    {
        $this->suivi = $suivi;

        return $this;
    }

    public function getAffectation(): ?Affectation
    {
        return $this->affectation;
    }

    public function setAffectation(?Affectation $affectation): self
    {
        $this->affectation = $affectation;

        return $this;
    }

    public function isWaitMaillingSummary(): bool
    {
        return $this->waitMaillingSummary;
    }

    public function setWaitMaillingSummary(bool $waitMaillingSummary): static
    {
        $this->waitMaillingSummary = $waitMaillingSummary;

        return $this;
    }

    public function getMaillingSummarySentAt(): ?\DateTimeImmutable
    {
        return $this->maillingSummarySentAt;
    }

    public function setMaillingSummarySentAt(?\DateTimeImmutable $maillingSummarySentAt): static
    {
        $this->maillingSummarySentAt = $maillingSummarySentAt;

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): static
    {
        $this->deleted = $deleted;

        return $this;
    }
}
