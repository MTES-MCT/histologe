<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class FailedEmail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'json')]
    private array $toEmail;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $fromEmail;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $fromFullname;

    #[ORM\Column(type: 'json')]
    private array $params = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'integer')]
    private int $retryCount = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastAttemptAt;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message;

    #[ORM\ManyToOne(targetEntity: Territory::class)]
    private ?Territory $territory;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $user;

    #[ORM\ManyToOne(targetEntity: Signalement::class)]
    private ?Signalement $signalement;

    #[ORM\ManyToOne(targetEntity: Suivi::class)]
    private ?Suivi $suivi;

    #[ORM\ManyToOne(targetEntity: SignalementDraft::class)]
    private ?SignalementDraft $signalementDraft;

    #[ORM\ManyToOne(targetEntity: Intervention::class)]
    private ?Intervention $intervention;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $previousVisiteDate;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $attachment;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $motif;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $cronLabel;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $cronCount;

    #[ORM\Column]
    private bool $notifyUsager = false;

    #[ORM\Column(type: 'boolean')]
    private bool $isResendSuccessful = false;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getToEmail(): array
    {
        return $this->toEmail;
    }

    public function setToEmail(array $toEmail): static
    {
        $this->toEmail = $toEmail;

        return $this;
    }

    public function getFromEmail(): ?string
    {
        return $this->fromEmail;
    }

    public function setFromEmail(?string $fromEmail): static
    {
        $this->fromEmail = $fromEmail;

        return $this;
    }

    public function getFromFullname(): ?string
    {
        return $this->fromFullname;
    }

    public function setFromFullname(?string $fromFullname): static
    {
        $this->fromFullname = $fromFullname;

        return $this;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $params): static
    {
        $this->params = $params;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function setRetryCount(int $retryCount): static
    {
        $this->retryCount = $retryCount;

        return $this;
    }

    public function getLastAttemptAt(): ?\DateTimeImmutable
    {
        return $this->lastAttemptAt;
    }

    public function setLastAttemptAt(?\DateTimeImmutable $lastAttemptAt): static
    {
        $this->lastAttemptAt = $lastAttemptAt;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

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

    public function getSuivi(): ?Suivi
    {
        return $this->suivi;
    }

    public function setSuivi(?Suivi $suivi): static
    {
        $this->suivi = $suivi;

        return $this;
    }

    public function getSignalementDraft(): ?SignalementDraft
    {
        return $this->signalementDraft;
    }

    public function setSignalementDraft(?SignalementDraft $signalementDraft): static
    {
        $this->signalementDraft = $signalementDraft;

        return $this;
    }

    public function getIntervention(): ?Intervention
    {
        return $this->intervention;
    }

    public function setIntervention(?Intervention $intervention): static
    {
        $this->intervention = $intervention;

        return $this;
    }

    public function getPreviousVisiteDate(): ?\DateTimeImmutable
    {
        return $this->previousVisiteDate;
    }

    public function setPreviousVisiteDate(?\DateTimeImmutable $previousVisiteDate): static
    {
        $this->previousVisiteDate = $previousVisiteDate;

        return $this;
    }

    public function getAttachment(): mixed
    {
        return $this->attachment;
    }

    public function setAttachment(mixed $attachment): static
    {
        $this->attachment = $attachment;

        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getCronLabel(): ?string
    {
        return $this->cronLabel;
    }

    public function setCronLabel(?string $cronLabel): static
    {
        $this->cronLabel = $cronLabel;

        return $this;
    }

    public function getCronCount(): ?int
    {
        return $this->cronCount;
    }

    public function setCronCount(?int $cronCount): static
    {
        $this->cronCount = $cronCount;

        return $this;
    }

    public function getNotifyUsager(): bool
    {
        return $this->notifyUsager;
    }

    public function setNotifyUsager(bool $notifyUsager): static
    {
        $this->notifyUsager = $notifyUsager;

        return $this;
    }

    public function isResendSuccessful(): bool
    {
        return $this->isResendSuccessful;
    }

    public function setResendSuccessful(bool $isResendSuccessful): self
    {
        $this->isResendSuccessful = $isResendSuccessful;

        return $this;
    }
}
