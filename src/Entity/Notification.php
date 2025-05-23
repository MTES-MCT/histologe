<?php

namespace App\Entity;

use App\Entity\Enum\NotificationType;
use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    public const int MAX_LIST_PAGINATION = 100;
    public const string EXPIRATION_PERIOD = '- 30 days';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'notifications')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $isSeen = null;

    #[ORM\Column(
        type: 'string',
        enumType: NotificationType::class,
        options: ['comment' => 'Value possible enum NotificationType'])]
    private ?NotificationType $type = null;

    #[ORM\ManyToOne(targetEntity: Signalement::class)]
    private ?Signalement $signalement = null;

    #[ORM\ManyToOne(targetEntity: Suivi::class)]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Suivi $suivi = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Affectation::class, inversedBy: 'notifications')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?Affectation $affectation = null;

    #[ORM\Column]
    private ?bool $waitMailingSummary = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $mailingSummarySentAt = null;

    #[ORM\Column]
    private ?bool $deleted = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $seenAt = null;

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

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getIsSeen(): ?bool
    {
        return $this->isSeen;
    }

    public function setIsSeen(bool $isSeen): static
    {
        $this->isSeen = $isSeen;

        return $this;
    }

    public function getType(): NotificationType
    {
        return $this->type;
    }

    public function setType(NotificationType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSignalement(): ?Signalement
    {
        return $this->signalement;
    }

    public function setSignalement(?Signalement $signalement): static
    {
        $this->signalement = $signalement;

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

    public function getSuivi(): ?Suivi
    {
        return $this->suivi;
    }

    public function setSuivi(?Suivi $suivi): static
    {
        $this->suivi = $suivi;

        return $this;
    }

    public function getAffectation(): ?Affectation
    {
        return $this->affectation;
    }

    public function setAffectation(?Affectation $affectation): static
    {
        $this->affectation = $affectation;

        return $this;
    }

    public function isWaitMailingSummary(): bool
    {
        return $this->waitMailingSummary;
    }

    public function setWaitMailingSummary(bool $waitMailingSummary): static
    {
        $this->waitMailingSummary = $waitMailingSummary;

        return $this;
    }

    public function getMailingSummarySentAt(): ?\DateTimeImmutable
    {
        return $this->mailingSummarySentAt;
    }

    public function setMailingSummarySentAt(?\DateTimeImmutable $mailingSummarySentAt): static
    {
        $this->mailingSummarySentAt = $mailingSummarySentAt;

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

    public function getSeenAt(): ?\DateTimeImmutable
    {
        return $this->seenAt;
    }

    public function setSeenAt(?\DateTimeImmutable $seenAt): static
    {
        $this->seenAt = $seenAt;

        return $this;
    }
}
