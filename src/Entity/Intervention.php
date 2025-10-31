<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Behaviour\EntitySanitizerInterface;
use App\Entity\Behaviour\TimestampableTrait;
use App\Entity\Enum\DocumentType;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\ProcedureType;
use App\Repository\InterventionRepository;
use App\Service\TimezoneProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: InterventionRepository::class)]
#[ORM\HasLifecycleCallbacks()]
class Intervention implements EntityHistoryInterface, EntitySanitizerInterface
{
    use TimestampableTrait;

    public const string STATUS_PLANNED = 'PLANNED';
    public const string STATUS_DONE = 'DONE';
    public const string STATUS_CANCELED = 'CANCELED';
    public const string STATUS_NOT_DONE = 'NOT_DONE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private ?string $uuid;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $scheduledAt = null;
    private ?\DateTimeImmutable $previousScheduledAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $registeredAt = null;

    #[ORM\ManyToOne(inversedBy: 'interventions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Signalement $signalement = null;

    #[ORM\ManyToOne(inversedBy: 'interventions')]
    private ?Partner $partner = null;

    #[ORM\Column(type: 'string', enumType: InterventionType::class)]
    private ?InterventionType $type = null;

    #[ORM\Column(type: 'string')]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentBeforeVisite = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    /** @var array<ProcedureType> $concludeProcedure */
    #[ORM\Column(type: Types::SIMPLE_ARRAY, length: 255, nullable: true, enumType: ProcedureType::class)]
    private array $concludeProcedure = [];

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $conclusionVisiteEditedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reminderBeforeSentAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reminderConclusionSentAt = null;

    #[ORM\Column(nullable: true)]
    private ?bool $occupantPresent = null;

    #[ORM\Column(nullable: true)]
    private ?bool $proprietairePresent = null;

    #[ORM\Column(nullable: true)]
    private ?string $doneBy = null;

    #[ORM\Column(nullable: true, options: ['comment' => 'Provider name have created the intervention'])]
    private ?string $providerName = null;

    #[ORM\Column(nullable: true, options: ['comment' => 'Unique id used by the provider'])]
    private ?int $providerId = null;

    /** @var array<mixed> $additionalInformation */
    #[ORM\Column(nullable: true)]
    private ?array $additionalInformation = [];

    /** @var Collection<int, File> $files */
    #[ORM\OneToMany(mappedBy: 'intervention', targetEntity: File::class, cascade: ['persist'])]
    private Collection $files;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $externalOperator = null;

    /** @var array<string, array<string, string>>
     *
     *  Ce tableau contient les modifications des champs sous forme structurée,
     *  chaque entrée représentant un champ modifié avec ses anciennes et nouvelles valeurs.
     *
     *  Exemple :
     *  [
     *      'details' ⇒ ['old' ⇒ 'Ancien commentaire', 'new' ⇒ 'Nouveau commentaire'],
     *      'concludeProcedure' ⇒ ['old' ⇒ 'Autre', 'new' ⇒ 'Insalubrité'],
     *  ]
     */
    private ?array $changesForMail = [];

    public function __construct()
    {
        $this->files = new ArrayCollection();
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

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?\DateTimeImmutable $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getScheduledAtFormated(): string
    {
        if ($this->getScheduledAt()->format('His') > 0) {
            $timezone = $this->getPartner()?->getTerritory()?->getTimezone() ?? TimezoneProvider::TIMEZONE_EUROPE_PARIS;

            return $this->getScheduledAt()
                        ->setTimezone(new \DateTimeZone($timezone))
                        ->format('d/m/Y à H:i');
        }

        return $this->getScheduledAt()->format('d/m/Y');
    }

    public function getRegisteredAt(): ?\DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(?\DateTimeImmutable $registeredAt): static
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    public function hasScheduledDatePassed(): bool
    {
        return $this->getScheduledAt()->format('Y-m-d') <= (new \DateTimeImmutable())->format('Y-m-d');
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
        if (!$this->partner && $this->externalOperator) {
            $externalPartner = new Partner();
            $externalPartner->setNom($this->externalOperator);
            $externalPartner->setType(PartnerType::OPERATEUR_VISITES_ET_TRAVAUX);

            return $externalPartner;
        }

        return $this->partner;
    }

    public function setPartner(?Partner $partner): static
    {
        $this->partner = $partner;

        return $this;
    }

    public function getType(): ?InterventionType
    {
        return $this->type;
    }

    public function setType(InterventionType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCommentBeforeVisite(): ?string
    {
        return $this->commentBeforeVisite;
    }

    public function setCommentBeforeVisite(?string $commentBeforeVisite): static
    {
        $this->commentBeforeVisite = $commentBeforeVisite;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;

        return $this;
    }

    /** @return array<ProcedureType> */
    public function getConcludeProcedure(): ?array
    {
        return $this->concludeProcedure;
    }

    public function getConcludeProcedureString(string $separator = ', '): string
    {
        if (empty($this->concludeProcedure)) {
            return '';
        }

        return implode(
            $separator,
            array_map(fn (ProcedureType $procedure) => $procedure->label(), $this->concludeProcedure)
        );
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getChangesForMail(): ?array
    {
        return $this->changesForMail;
    }

    /**
     * @param array<string, array<string, string>> $changesForMail
     */
    public function setChangesForMail(array $changesForMail): static
    {
        $this->changesForMail = $changesForMail;

        return $this;
    }

    /** @param array<ProcedureType> $concludeProcedure */
    public function setConcludeProcedure(?array $concludeProcedure): static
    {
        $this->concludeProcedure = $concludeProcedure;

        return $this;
    }

    public function getReminderBeforeSentAt(): ?\DateTimeImmutable
    {
        return $this->reminderBeforeSentAt;
    }

    public function setReminderBeforeSentAt(?\DateTimeImmutable $reminderBeforeSentAt): static
    {
        $this->reminderBeforeSentAt = $reminderBeforeSentAt;

        return $this;
    }

    public function getConclusionVisiteEditedAt(): ?\DateTimeImmutable
    {
        return $this->conclusionVisiteEditedAt;
    }

    public function setConclusionVisiteEditedAt(?\DateTimeImmutable $conclusionVisiteEditedAt): self
    {
        $this->conclusionVisiteEditedAt = $conclusionVisiteEditedAt;

        return $this;
    }

    public function getReminderConclusionSentAt(): ?\DateTimeImmutable
    {
        return $this->reminderConclusionSentAt;
    }

    public function setReminderConclusionSentAt(?\DateTimeImmutable $reminderConclusionSentAt): static
    {
        $this->reminderConclusionSentAt = $reminderConclusionSentAt;

        return $this;
    }

    public function isOccupantPresent(): ?bool
    {
        return $this->occupantPresent;
    }

    public function setOccupantPresent(?bool $occupantPresent): static
    {
        $this->occupantPresent = $occupantPresent;

        return $this;
    }

    public function isProprietairePresent(): ?bool
    {
        return $this->proprietairePresent;
    }

    public function setProprietairePresent(?bool $proprietairePresent): static
    {
        $this->proprietairePresent = $proprietairePresent;

        return $this;
    }

    public function getDoneBy(): ?string
    {
        return $this->doneBy;
    }

    public function setDoneBy(?string $doneBy): static
    {
        $this->doneBy = $doneBy;

        return $this;
    }

    public function getProviderName(): ?string
    {
        return $this->providerName;
    }

    public function setProviderName(?string $providerName): static
    {
        $this->providerName = $providerName;

        return $this;
    }

    public function getProviderId(): ?int
    {
        return $this->providerId;
    }

    public function setProviderId(?int $providerId): static
    {
        $this->providerId = $providerId;

        return $this;
    }

    /** @return array<mixed> */
    public function getAdditionalInformation(): ?array
    {
        return $this->additionalInformation;
    }

    /** @param array<mixed> $additionalInformation */
    public function setAdditionalInformation(?array $additionalInformation): static
    {
        $this->additionalInformation = $additionalInformation;

        return $this;
    }

    /**
     * @return Collection<int, File>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(File $file): static
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setIntervention($this);
        }

        return $this;
    }

    public function removeFile(File $file): static
    {
        if ($this->files->removeElement($file)) {
            // set the owning side to null (unless already changed)
            if ($file->getIntervention() === $this) {
                $file->setIntervention(null);
            }
        }

        return $this;
    }

    public function getRapportDeVisite(): ?File
    {
        foreach ($this->files as $file) {
            if (DocumentType::PROCEDURE_RAPPORT_DE_VISITE === $file->getDocumentType()) {
                return $file;
            }
        }

        return null;
    }

    /** @return array<HistoryEntryEvent> */
    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }

    public function sanitize(HtmlSanitizerInterface $htmlSanitizer): void
    {
        if (!empty($this->details)) {
            $this->details = $htmlSanitizer->sanitize($this->details);
        }
        if (!empty($this->commentBeforeVisite)) {
            $this->commentBeforeVisite = $htmlSanitizer->sanitize($this->commentBeforeVisite);
        }
    }

    public function getDescription(): string
    {
        return $this->getDetails() ?? '';
    }

    public function getExternalOperator(): ?string
    {
        return $this->externalOperator;
    }

    public function setExternalOperator(?string $externalOperator): static
    {
        $this->externalOperator = $externalOperator;

        return $this;
    }

    public function getPreviousScheduledAt(): ?\DateTimeImmutable
    {
        return $this->previousScheduledAt;
    }

    public function setPreviousScheduledAt(?\DateTimeImmutable $previousScheduledAt): static
    {
        $this->previousScheduledAt = $previousScheduledAt;

        return $this;
    }
}
