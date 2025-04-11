<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\MotifRefus;
use App\Repository\AffectationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AffectationRepository::class)]
class Affectation implements EntityHistoryInterface
{
    public const int STATUS_WAIT = 0;
    public const int STATUS_ACCEPTED = 1;
    public const int STATUS_REFUSED = 2;
    public const int STATUS_CLOSED = 3;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private ?string $uuid;

    #[ORM\ManyToOne(targetEntity: Signalement::class, inversedBy: 'affectations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Signalement $signalement = null;

    #[ORM\ManyToOne(targetEntity: Partner::class, inversedBy: 'affectations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Partner $partner = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $answeredAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'integer')]
    private ?int $statut = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isSynchronized = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $answeredBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $affectedBy = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: MotifRefus::class)]
    private ?MotifRefus $motifRefus = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: MotifCloture::class)]
    private ?MotifCloture $motifCloture = null;

    #[ORM\OneToMany(mappedBy: 'affectation', targetEntity: Notification::class, cascade: ['remove'])]
    private Collection $notifications;

    #[ORM\ManyToOne(targetEntity: Territory::class, inversedBy: 'affectations')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Territory $territory;

    private ?int $nextStatut = null;
    private ?bool $hasNotificationUsagerToCreate = null;

    public function __construct()
    {
        $this->statut = self::STATUS_WAIT;
        $this->createdAt = new \DateTimeImmutable();
        $this->notifications = new ArrayCollection();
        $this->uuid = Uuid::v4();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
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

    public function getPartner(): ?Partner
    {
        return $this->partner;
    }

    public function setPartner(?Partner $partner): static
    {
        $this->partner = $partner;

        return $this;
    }

    public function getAnsweredAt(): ?\DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function setAnsweredAt(\DateTimeImmutable $answeredAt): static
    {
        $this->answeredAt = $answeredAt;

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

    public function getStatut(): ?int
    {
        return $this->statut;
    }

    public function setStatut(int $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getAnsweredBy(): ?User
    {
        return $this->answeredBy;
    }

    public function setAnsweredBy(?User $answeredBy): static
    {
        $this->answeredBy = $answeredBy;

        return $this;
    }

    public function getAffectedBy(): ?User
    {
        return $this->affectedBy;
    }

    public function setAffectedBy(?User $affectedBy): static
    {
        $this->affectedBy = $affectedBy;

        return $this;
    }

    public function getMotifRefus(): ?MotifRefus
    {
        return $this->motifRefus;
    }

    public function setMotifRefus(?MotifRefus $motifRefus): static
    {
        $this->motifRefus = $motifRefus;

        return $this;
    }

    public function getMotifCloture(): ?MotifCloture
    {
        return $this->motifCloture;
    }

    public function setMotifCloture(?MotifCloture $motifCloture): static
    {
        $this->motifCloture = $motifCloture;

        return $this;
    }

    /**
     * @return Collection|Notification[]
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications[] = $notification;
            $notification->setAffectation($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getAffectation() === $this) {
                $notification->setAffectation(null);
            }
        }

        return $this;
    }

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): static
    {
        $this->territory = $territory;

        return $this;
    }

    public function isSynchronized(): bool
    {
        return $this->isSynchronized;
    }

    public function setIsSynchronized(bool $isSynchronized): static
    {
        $this->isSynchronized = $isSynchronized;

        return $this;
    }

    public function getAffectationLabel(): string
    {
        return match ($this->getStatut()) {
            self::STATUS_WAIT => 'En attente...',
            self::STATUS_ACCEPTED => 'Accepté',
            self::STATUS_REFUSED => 'Refusé',
            self::STATUS_CLOSED => 'Cloturé',
            default => 'Unexpected affectation status : '.$this->getStatut(),
        };
    }

    public function getNextStatut(): ?int
    {
        return $this->nextStatut;
    }

    public function setNextStatut(int $nextStatut): void
    {
        $this->nextStatut = $nextStatut;
    }

    public function getHasNotificationUsagerToCreate(): ?bool
    {
        return $this->hasNotificationUsagerToCreate;
    }

    public function setHasNotificationUsagerToCreate(?bool $hasNotificationUsagerToCreate): void
    {
        $this->hasNotificationUsagerToCreate = $hasNotificationUsagerToCreate;
    }

    public function clearMotifs(): void
    {
        $this->motifRefus = null;
        $this->motifCloture = null;
    }

    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }
}
