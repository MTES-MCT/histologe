<?php

namespace App\Entity;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Behaviour\TimestampableTrait;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementDraftStatus;
use App\Repository\SignalementDraftRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SignalementDraftRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class SignalementDraft
{
    use TimestampableTrait;
    public const string EXPIRATION_PERIOD = '- 6 months';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string')]
    private ?string $uuid = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: ProfileDeclarant::class)]
    private ?ProfileDeclarant $profileDeclarant = null;

    #[ORM\Column(length: 255)]
    private ?string $emailDeclarant = null;

    #[ORM\Column(length: 255)]
    private ?string $addressComplete = null;

    /** @var array<mixed> $payload */
    #[ORM\Column(type: 'json')]
    private ?array $payload = [];

    #[ORM\Column(length: 128)]
    private ?string $currentStep = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: SignalementDraftStatus::class)]
    private ?SignalementDraftStatus $status = null;

    /** @var Collection<int, Signalement> $signalements */
    #[ORM\OneToMany(mappedBy: 'createdFrom', targetEntity: Signalement::class)]
    private Collection $signalements;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $checksum;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $pendingDraftRemindedAt = null;

    private ?SignalementDraftRequest $signalementDraftRequest = null;

    public function __construct()
    {
        $this->uuid = Uuid::v4();
        $this->signalements = new ArrayCollection();
        $this->status = SignalementDraftStatus::EN_COURS;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getProfileDeclarant(): ?ProfileDeclarant
    {
        return $this->profileDeclarant;
    }

    public function setProfileDeclarant(ProfileDeclarant $profileDeclarant): self
    {
        $this->profileDeclarant = $profileDeclarant;

        return $this;
    }

    public function getEmailDeclarant(): ?string
    {
        return $this->emailDeclarant;
    }

    public function setEmailDeclarant(string $emailDeclarant): self
    {
        $this->emailDeclarant = $emailDeclarant;
        $this->setChecksum();

        return $this;
    }

    public function getAddressComplete(): ?string
    {
        return $this->addressComplete;
    }

    public function setAddressComplete(?string $addressComplete): self
    {
        $this->addressComplete = $addressComplete;
        $this->setChecksum();

        return $this;
    }

    /** @return array<mixed> */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    /** @param array<mixed> $payload */
    public function setPayload(array $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function getCurrentStep(): ?string
    {
        return $this->currentStep;
    }

    public function setCurrentStep(string $currentStep): self
    {
        $this->currentStep = $currentStep;

        return $this;
    }

    public function getStatus(): ?SignalementDraftStatus
    {
        return $this->status;
    }

    public function setStatus(SignalementDraftStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getSignalementDraftRequest(): ?SignalementDraftRequest
    {
        return $this->signalementDraftRequest;
    }

    public function setSignalementDraftRequest(?SignalementDraftRequest $signalementDraftRequest): self
    {
        $this->signalementDraftRequest = $signalementDraftRequest;

        return $this;
    }

    /**
     * @return Collection<int, Signalement>
     */
    public function getSignalements(): Collection
    {
        return $this->signalements;
    }

    public function addSignalement(Signalement $signalement): self
    {
        if (!$this->signalements->contains($signalement)) {
            $this->signalements->add($signalement);
            $signalement->setCreatedFrom($this);
        }

        return $this;
    }

    public function removeSignalement(Signalement $signalement): self
    {
        if ($this->signalements->removeElement($signalement)) {
            // set the owning side to null (unless already changed)
            if ($signalement->getCreatedFrom() === $this) {
                $signalement->setCreatedFrom(null);
            }
        }

        return $this;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function setChecksum(): self
    {
        $this->checksum = $this->calculateChecksum();

        return $this;
    }

    public function getPendingDraftRemindedAt(): ?\DateTimeImmutable
    {
        return $this->pendingDraftRemindedAt;
    }

    public function setPendingDraftRemindedAtValue(): self
    {
        $this->pendingDraftRemindedAt = new \DateTimeImmutable();

        return $this;
    }

    public function calculateChecksum(): string
    {
        $dataToHash = $this->emailDeclarant.$this->addressComplete;

        return hash('sha256', $dataToHash);
    }
}
