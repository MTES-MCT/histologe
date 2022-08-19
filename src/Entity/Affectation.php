<?php

namespace App\Entity;

use App\Repository\AffectationRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AffectationRepository::class)]
class Affectation
{
    public const STATUS_WAIT = 0;
    public const STATUS_ACCEPTED = 1;
    public const STATUS_REFUSED = 2;
    public const STATUS_CLOSED = 3;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Signalement::class, inversedBy: 'affectations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Signalement $signalement;

    #[ORM\ManyToOne(targetEntity: Partner::class, inversedBy: 'affectations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Partner $partner;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $answeredAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'integer')]
    private int $statut;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $answeredBy;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $affectedBy;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $motifCloture;

    #[ORM\OneToMany(mappedBy: 'affectation', targetEntity: Notification::class)]
    private $notifications;

    #[ORM\ManyToOne(targetEntity: Territory::class, inversedBy: 'affectations')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Territory $territory;

    public function __construct()
    {
        $this->statut = self::STATUS_WAIT;
        $this->createdAt = new DateTimeImmutable();
        $this->notifications = new ArrayCollection();
    }

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

    public function getPartner(): ?Partner
    {
        return $this->partner;
    }

    public function setPartner(?Partner $partner): self
    {
        $this->partner = $partner;

        return $this;
    }

    public function getAnsweredAt(): ?DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function setAnsweredAt(DateTimeImmutable $answeredAt): self
    {
        $this->answeredAt = $answeredAt;

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

    public function getStatut(): ?int
    {
        return $this->statut;
    }

    public function setStatut(int $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getAnsweredBy(): ?User
    {
        return $this->answeredBy;
    }

    public function setAnsweredBy(?User $answeredBy): self
    {
        $this->answeredBy = $answeredBy;

        return $this;
    }

    public function getAffectedBy(): ?User
    {
        return $this->affectedBy;
    }

    public function setAffectedBy(?User $affectedBy): self
    {
        $this->affectedBy = $affectedBy;

        return $this;
    }

    public function getMotifCloture(): ?string
    {
        return $this->motifCloture;
    }

    public function setMotifCloture(?string $motifCloture): self
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

    public function addNotification(Notification $notification): self
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications[] = $notification;
            $notification->setAffectation($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): self
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

    public function setTerritory(?Territory $territory): self
    {
        $this->territory = $territory;

        return $this;
    }
}
