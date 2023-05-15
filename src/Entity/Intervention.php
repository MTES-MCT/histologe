<?php

namespace App\Entity;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\ProcedureType;
use App\Repository\InterventionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterventionRepository::class)]
class Intervention
{
    public const STATUS_PLANNED = 'PLANNED';
    public const STATUS_DONE = 'DONE';
    public const STATUS_CANCELED = 'CANCELED';
    public const STATUS_NOT_DONE = 'NOT_DONE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\ManyToOne(inversedBy: 'interventions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Signalement $signalement = null;

    #[ORM\ManyToOne(inversedBy: 'interventions')]
    private ?Partner $partner = null;

    #[ORM\Column(type: 'string', enumType: InterventionType::class)]
    private ?InterventionType $type = null;

    #[ORM\Column(type: 'string')]
    private ?string $status = null;

    #[ORM\Column]
    private array $documents = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, length: 255, nullable: true, enumType: ProcedureType::class)]
    private array $concludeProcedure = [];

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reminderBeforeSentAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reminderConclusionSentAt = null;

    #[ORM\Column(nullable: true)]
    private ?bool $occupantPresent = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?\DateTimeImmutable $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;

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

    public function getPartner(): ?Partner
    {
        return $this->partner;
    }

    public function setPartner(?Partner $partner): self
    {
        $this->partner = $partner;

        return $this;
    }

    public function getType(): ?InterventionType
    {
        return $this->type;
    }

    public function setType(InterventionType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getDocuments(): array
    {
        return $this->documents;
    }

    public function setDocuments(array $documents): self
    {
        $this->documents = $documents;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getConcludeProcedure(): ?array
    {
        return $this->concludeProcedure;
    }

    public function setConcludeProcedure(?array $concludeProcedure): self
    {
        $this->concludeProcedure = $concludeProcedure;

        return $this;
    }

    public function getReminderBeforeSentAt(): ?\DateTimeImmutable
    {
        return $this->reminderBeforeSentAt;
    }

    public function setReminderBeforeSentAt(?\DateTimeImmutable $reminderBeforeSentAt): self
    {
        $this->reminderBeforeSentAt = $reminderBeforeSentAt;

        return $this;
    }

    public function getReminderConclusionSentAt(): ?\DateTimeImmutable
    {
        return $this->reminderConclusionSentAt;
    }

    public function setReminderConclusionSentAt(?\DateTimeImmutable $reminderConclusionSentAt): self
    {
        $this->reminderConclusionSentAt = $reminderConclusionSentAt;

        return $this;
    }

    public function isOccupantPresent(): ?bool
    {
        return $this->occupantPresent;
    }

    public function setOccupantPresent(?bool $occupantPresent): self
    {
        $this->occupantPresent = $occupantPresent;

        return $this;
    }
}
