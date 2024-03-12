<?php

namespace App\Service\Mailer;

use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Entity\Territory;
use App\Entity\User;

class NotificationMail
{
    public function __construct(
        private readonly NotificationMailerType $type,
        private readonly array|string $to,
        private readonly ?string $fromEmail = null,
        private readonly ?string $fromFullname = null,
        private readonly ?string $message = null,
        private readonly ?Territory $territory = null,
        private readonly ?User $user = null,
        private readonly ?Signalement $signalement = null,
        private readonly ?SignalementDraft $signalementDraft = null,
        private readonly ?Intervention $intervention = null,
        private readonly ?\DateTimeImmutable $previousVisiteDate = null,
        private readonly mixed $event = null,
        private readonly mixed $attachment = null,
        private readonly ?string $motif = null,
        private readonly ?string $cronLabel = null,
        private readonly ?int $cronCount = null,
        private readonly array $params = [],
    ) {
    }

    public function getType(): NotificationMailerType
    {
        return $this->type;
    }

    public function getTo(): array|string
    {
        return $this->to;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function getEmails(): array
    {
        return \is_array($this->to) ? $this->to : [$this->to];
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getSignalement(): ?Signalement
    {
        return $this->signalement;
    }

    public function getSignalementDraft(): ?SignalementDraft
    {
        return $this->signalementDraft;
    }

    public function getIntervention(): ?Intervention
    {
        return $this->intervention;
    }

    public function getPreviousVisiteDate(): ?\DateTimeImmutable
    {
        return $this->previousVisiteDate;
    }

    public function getEvent(): mixed
    {
        return $this->event;
    }

    public function getAttachment(): mixed
    {
        return $this->attachment;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function getFromEmail(): ?string
    {
        return $this->fromEmail;
    }

    public function getFromFullname(): ?string
    {
        return $this->fromFullname;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getCronLabel(): ?string
    {
        return $this->cronLabel;
    }

    public function getCronCount(): ?int
    {
        return $this->cronCount;
    }
}
