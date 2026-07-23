<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\MotifRefus;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\TravauxMiseEnConformite;
use App\Repository\AffectationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: AffectationRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_affectation_signalement_partner', columns: ['signalement_id', 'partner_id'])]
#[ORM\Index(name: 'idx_affectation_statut_partner_sig', columns: ['statut', 'partner_id', 'signalement_id'])]
class Affectation implements EntityHistoryInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $uuid;

    #[ORM\ManyToOne(targetEntity: Signalement::class, inversedBy: 'affectations')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Signalement $signalement = null;

    #[ORM\ManyToOne(targetEntity: Partner::class, inversedBy: 'affectations')]
    #[ORM\JoinColumn(nullable: false)]
    private Partner $partner;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $answeredAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'string', enumType: AffectationStatus::class)]
    private AffectationStatus $statut;

    #[ORM\Column(type: 'boolean')]
    private bool $isSynchronized = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $answeredBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $affectedBy = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: MotifRefus::class)]
    private ?MotifRefus $motifRefus = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: MotifCloture::class)]
    #[Assert\NotBlank(groups: ['close_affectation'], message: 'Veuillez préciser le motif de clôture.')]
    private ?MotifCloture $motifCloture = null;

    /** @var Collection<int, Notification> $notifications */
    #[ORM\OneToMany(mappedBy: 'affectation', targetEntity: Notification::class, cascade: ['remove'])]
    private Collection $notifications;

    #[ORM\ManyToOne(targetEntity: Territory::class, inversedBy: 'affectations')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Territory $territory;

    private ?AffectationStatus $nextStatut = null;
    private ?bool $hasNotificationUsagerToCreate = null;

    #[ORM\Column(nullable: true, enumType: TravauxMiseEnConformite::class)]
    private ?TravauxMiseEnConformite $travauxMiseEnConformite = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(groups: ['close_affectation'], message: 'Veuillez commenter la clôture de l\'affectation.')]
    #[Assert\Length(min: 16, groups: ['close_affectation'], minMessage: 'La précision doit contenir au moins 10 caractères.')] // on compte 16 pour une limite de 10 car le message est emglobé par <p></p> par l'éditeur de texte
    private ?string $precisionsCloture = null;

    public function __construct()
    {
        $this->statut = AffectationStatus::WAIT;
        $this->createdAt = new \DateTimeImmutable();
        $this->notifications = new ArrayCollection();
        $this->uuid = Uuid::v4();
    }

    #[Assert\Callback(groups: ['close_affectation'])]
    public function validateCloseAffectation(ExecutionContextInterface $context): void
    {
        if (!$this->travauxMiseEnConformite && in_array($this->motifCloture, MotifCloture::getListNeedTravauxPrecisions(), true)) {
            $context->buildViolation('Veuillez préciser si les travaux de mise en conformité du logement ont été réalisés.')
                ->atPath('travauxMiseEnConformite')
                ->addViolation();
        }
    }

    public function __toString(): string
    {
        return $this->createdAt->format('d/m/y H:i').' '.$this->getPartner()->getNom().' - '.$this->getStatut()->value.'';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
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

    public function getStatut(): AffectationStatus
    {
        return $this->statut;
    }

    public function setStatut(AffectationStatus $statut): static
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
     * @return Collection<int, Notification>
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

    public function getNextStatut(): ?AffectationStatus
    {
        return $this->nextStatut;
    }

    public function setNextStatut(AffectationStatus $nextStatut): void
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
        $this->travauxMiseEnConformite = null;
        $this->precisionsCloture = null;
    }

    public function isSynchronizeWithEsabora(): bool
    {
        if ((PartnerType::ARS === $this->getPartner()->getType()
            || PartnerType::COMMUNE_SCHS === $this->getPartner()->getType()
            || PartnerType::EPCI == $this->getPartner()->getType()
        ) && $this->isSynchronized) {
            return true;
        }

        return false;
    }

    /** @return array<HistoryEntryEvent> */
    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }

    public function getTravauxMiseEnConformite(): ?TravauxMiseEnConformite
    {
        return $this->travauxMiseEnConformite;
    }

    public function setTravauxMiseEnConformite(?TravauxMiseEnConformite $travauxMiseEnConformite): static
    {
        $this->travauxMiseEnConformite = $travauxMiseEnConformite;

        return $this;
    }

    public function getPrecisionsCloture(): ?string
    {
        return $this->precisionsCloture;
    }

    public function setPrecisionsCloture(?string $precisionsCloture): static
    {
        $this->precisionsCloture = $precisionsCloture;

        return $this;
    }
}
