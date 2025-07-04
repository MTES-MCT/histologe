<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryCollectionInterface;
use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\DebutDesordres;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\MotifRefus;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\ProprioType;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Model\InformationComplementaire;
use App\Entity\Model\InformationProcedure;
use App\Entity\Model\SituationFoyer;
use App\Entity\Model\TypeCompositionLogement;
use App\Repository\SignalementRepository;
use App\Service\TimezoneProvider;
use App\Utils\CommuneHelper;
use App\Utils\Phone;
use App\Utils\TrimHelper;
use App\Validator as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: SignalementRepository::class)]
#[ORM\Index(columns: ['statut'], name: 'idx_signalement_statut')]
#[ORM\Index(columns: ['created_at'], name: 'idx_signalement_created_at')]
#[ORM\Index(columns: ['is_imported'], name: 'idx_signalement_is_imported')]
#[ORM\Index(columns: ['uuid'], name: 'idx_signalement_uuid')]
#[ORM\Index(columns: ['code_suivi'], name: 'idx_signalement_code_suivi')]
#[ORM\Index(columns: ['cp_occupant'], name: 'idx_signalement_cp_occupant')]
class Signalement implements EntityHistoryInterface, EntityHistoryCollectionInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $uuid = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: ProfileDeclarant::class)]
    private ?ProfileDeclarant $profileDeclarant = null;

    /** @var Collection<int, Situation> $situations */
    #[ORM\ManyToMany(targetEntity: Situation::class, inversedBy: 'signalements')]
    private Collection $situations;

    /** @var Collection<int, Critere> $criteres */
    #[ORM\ManyToMany(targetEntity: Critere::class, inversedBy: 'signalements')]
    private Collection $criteres;

    /** @var Collection<int, Criticite> $criticites */
    #[ORM\ManyToMany(targetEntity: Criticite::class, inversedBy: 'signalements')]
    private Collection $criticites;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $details = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isProprioAverti = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $nbAdultes = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $nbEnfantsM6 = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $nbEnfantsP6 = null;

    #[ORM\Column(type: 'string', length: 3, nullable: true)]
    private ?string $isAllocataire = null;

    #[ORM\Column(type: 'string', length: 25, nullable: true)]
    private ?string $numAllocataire = null;

    #[ORM\Column(type: 'string', length: 15, nullable: true)]
    #[Assert\Length(max: 15, groups: ['bo_step_logement'])]
    private ?string $natureLogement = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $superficie = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $loyer = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isBailEnCours = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isLogementVacant = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateEntree = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: ProprioType::class)]
    private ?ProprioType $typeProprio = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $nomProprio = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $denominationProprio = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $prenomProprio = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $adresseProprio = null;

    #[ORM\Column(type: 'string', length: 5, nullable: true)]
    #[Assert\Regex(pattern: '/^[0-9]{5}$/', message: 'Le code postal être composé de 5 chiffres.')]
    private ?string $codePostalProprio = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $villeProprio = null;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    #[AppAssert\TelephoneFormat]
    private ?string $telProprio = null;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    #[AppAssert\TelephoneFormat]
    private ?string $telProprioSecondaire = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Email(mode: Email::VALIDATION_MODE_STRICT, message: 'L\'adresse e-mail du bailleur n\'est pas valide.', groups: ['Default', 'bo_step_coordonnees'])]
    private ?string $mailProprio = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isLogementSocial = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isPreavisDepart = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isRelogement = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isRefusIntervention = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $raisonRefusIntervention = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isNotOccupant = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $nomDeclarant = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $prenomDeclarant = null;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    #[AppAssert\TelephoneFormat]
    private ?string $telDeclarant = null;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    #[AppAssert\TelephoneFormat]
    private ?string $telDeclarantSecondaire = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Email(mode: Email::VALIDATION_MODE_STRICT, message: 'L\'adresse e-mail du déclarant n\'est pas valide.', groups: ['Default', 'bo_step_coordonnees'])]
    private ?string $mailDeclarant = null;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $structureDeclarant = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $civiliteOccupant = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private ?string $nomOccupant = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private ?string $prenomOccupant = null;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    #[AppAssert\TelephoneFormat]
    private ?string $telOccupant = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Email(mode: Email::VALIDATION_MODE_STRICT, message: 'L\'adresse e-mail de l\'occupant n\'est pas valide.', groups: ['Default', 'bo_step_coordonnees'])]
    private ?string $mailOccupant = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100, groups: ['bo_step_address'])]
    private ?string $adresseOccupant = null;

    #[ORM\Column(type: 'string', length: 5, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[0-9]{5}$/', message: 'Le code postal doit être composé de 5 chiffres.', groups: ['Default', 'bo_step_address'])]
    private ?string $cpOccupant = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Length(max: 100, groups: ['bo_step_address'])]
    private ?string $villeOccupant = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $banIdOccupant = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $nomAgence = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $denominationAgence = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $prenomAgence = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $adresseAgence = null;

    #[ORM\Column(type: 'string', length: 5, nullable: true)]
    #[Assert\Regex(pattern: '/^[0-9]{5}$/', message: 'Le code postal être composé de 5 chiffres.')]
    private ?string $codePostalAgence = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $villeAgence = null;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    #[AppAssert\TelephoneFormat]
    private ?string $telAgence = null;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    #[AppAssert\TelephoneFormat]
    private ?string $telAgenceSecondaire = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Email(mode: Email::VALIDATION_MODE_STRICT, message: 'L\'adresse e-mail de l\'agence n\'est pas valide.', groups: ['Default', 'bo_step_coordonnees'])]
    private ?string $mailAgence = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $isCguAccepted = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $modifiedAt = null;

    #[ORM\Column(type: 'string', enumType: SignalementStatus::class)]
    private ?SignalementStatus $statut = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $reference = null;

    /** @var array<mixed> $jsonContent */
    #[ORM\Column(type: 'json')]
    private array $jsonContent = [];

    /** @var array<mixed> $geoloc */
    #[ORM\Column(type: 'json')]
    private array $geoloc = [];

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $montantAllocation = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'signalementsModified')]
    private ?User $modifiedBy = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'signalementsCreated')]
    private ?User $createdBy = null;

    /** @var Collection<int, Suivi> $suivis */
    #[ORM\OneToMany(mappedBy: 'signalement', targetEntity: Suivi::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $suivis;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSuiviAt = null;

    #[ORM\Column(nullable: true)]
    private ?string $lastSuiviBy = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $codeProcedure = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 5, groups: ['bo_step_address'])]
    private ?string $etageOccupant = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 3, groups: ['bo_step_address'])]
    private ?string $escalierOccupant = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 5, groups: ['bo_step_address'])]
    private ?string $numAppartOccupant = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255, groups: ['bo_step_address'])]
    private ?string $adresseAutreOccupant = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $inseeOccupant = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $manualAddressOccupant = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $codeSuivi = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $lienDeclarantOccupant = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isConsentementTiers = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isRsa = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $anneeConstruction = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $typeEnergieLogement = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $origineSignalement = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $situationOccupant = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $situationProOccupant = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $naissanceOccupants = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isLogementCollectif = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isConstructionAvant1949 = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isDiagSocioTechnique = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isFondSolidariteLogement = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isRisqueSurOccupation = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $proprioAvertiAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $nomReferentSocial = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $structureReferentSocial = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $numeroInvariant = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $numeroInvariantRial = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $nbPiecesLogement = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $nbChambresLogement = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $nbNiveauxLogement = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $nbOccupantsLogement = null;

    /** @var Collection<int, Affectation> $affectations */
    #[ORM\OneToMany(mappedBy: 'signalement', targetEntity: Affectation::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $affectations;

    #[ORM\Column(type: 'string', enumType: MotifCloture::class, nullable: true)]
    private ?MotifCloture $motifCloture = null;

    #[ORM\Column(type: 'string', enumType: MotifRefus::class, nullable: true)]
    private ?MotifRefus $motifRefus = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'signalementsClosed')]
    private ?User $closedBy = null;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    #[AppAssert\TelephoneFormat]
    private ?string $telOccupantBis = null;

    /** @var Collection<int, Tag> $tags */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'signalements')]
    #[ORM\JoinTable(name: 'tag_signalement')]
    private Collection $tags;

    #[ORM\ManyToOne(targetEntity: Territory::class, inversedBy: 'signalements')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Territory $territory = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isImported = null;

    /** @var Collection<int, SignalementQualification> $signalementQualifications */
    #[ORM\OneToMany(mappedBy: 'signalement', targetEntity: SignalementQualification::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $signalementQualifications;

    #[ORM\Column]
    private ?float $score = null;

    #[ORM\Column]
    private ?float $scoreLogement = null;

    #[ORM\Column]
    private ?float $scoreBatiment = null;

    /** @var Collection<int, Intervention> $interventions */
    #[ORM\OneToMany(mappedBy: 'signalement', targetEntity: Intervention::class, orphanRemoval: true)]
    private Collection $interventions;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isUsagerAbandonProcedure = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateNaissanceOccupant = null;

    /** @var Collection<int, File> $files */
    #[ORM\OneToMany(mappedBy: 'signalement', targetEntity: File::class, cascade: ['persist'])]
    private Collection $files;

    #[ORM\ManyToOne(inversedBy: 'signalements')]
    private ?SignalementDraft $createdFrom = null;

    #[ORM\Column(type: 'type_composition_logement', nullable: true)]
    private ?TypeCompositionLogement $typeCompositionLogement = null;

    #[ORM\Column(type: 'situation_foyer', nullable: true)]
    private ?SituationFoyer $situationFoyer = null;

    #[ORM\Column(type: 'information_procedure', nullable: true)]
    private ?InformationProcedure $informationProcedure = null;

    #[ORM\Column(type: 'information_complementaire', nullable: true)]
    private ?InformationComplementaire $informationComplementaire = null;

    /** @var Collection<int, DesordreCategorie> $desordreCategories */
    #[ORM\ManyToMany(targetEntity: DesordreCategorie::class, inversedBy: 'signalement', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'desordre_categorie_signalement')]
    private Collection $desordreCategories;

    /** @var Collection<int, DesordreCritere> $desordreCriteres */
    #[ORM\ManyToMany(targetEntity: DesordreCritere::class, inversedBy: 'signalement', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'desordre_critere_signalement')]
    private Collection $desordreCriteres;

    /** @var Collection<int, DesordrePrecision> $desordrePrecisions */
    #[ORM\ManyToMany(targetEntity: DesordrePrecision::class, inversedBy: 'signalement', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'desordre_precision_signalement')]
    private Collection $desordrePrecisions;

    #[ORM\ManyToOne(inversedBy: 'signalements')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Bailleur $bailleur = null;

    #[ORM\Column(nullable: true)]
    private ?bool $lastSuiviIsPublic = null;

    /** @var array<mixed> $synchroData */
    #[ORM\Column(nullable: true)]
    private ?array $synchroData = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rnbIdOccupant = null;

    #[ORM\Column(type: 'string', length: 15, nullable: true, enumType: DebutDesordres::class)]
    private ?DebutDesordres $debutDesordres = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $hasSeenDesordres = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comCloture = null;

    #[ORM\OneToOne(mappedBy: 'signalement', targetEntity: SignalementUsager::class)]
    private ?SignalementUsager $signalementUsager = null;

    public function __construct()
    {
        $this->situations = new ArrayCollection();
        $this->criteres = new ArrayCollection();
        $this->criticites = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->statut = SignalementStatus::NEED_VALIDATION;
        $this->uuid = Uuid::v4();
        $this->codeSuivi = Uuid::v4();
        $this->suivis = new ArrayCollection();
        $this->score = 0;
        $this->scoreLogement = 0;
        $this->scoreBatiment = 0;
        $this->affectations = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->isImported = false;
        $this->signalementQualifications = new ArrayCollection();
        $this->interventions = new ArrayCollection();
        $this->files = new ArrayCollection();
        $this->desordreCategories = new ArrayCollection();
        $this->desordreCriteres = new ArrayCollection();
        $this->desordrePrecisions = new ArrayCollection();
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, mixed $payload): void
    {
        // check mails
        if (!$this->mailDeclarant && !$this->mailOccupant) {
            $context->buildViolation('Vous devez renseigner au moins une adresse e-mail pour le déclarant ou l\'occupant.')
                ->atPath('mailDeclarant')
                ->atPath('mailOccupant')
                ->addViolation();
        }
        if ($this->mailDeclarant && $this->mailOccupant && $this->mailDeclarant === $this->mailOccupant) {
            $context->buildViolation('les adresses e-mails du déclarant et de l\'occupant sont identiques (laisser l\'adresse vide si l\'occupant n\'en dispose pas).')
                ->atPath('mailDeclarant')
                ->atPath('mailOccupant')
                ->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Situation>
     */
    public function getSituations(): Collection
    {
        return $this->situations;
    }

    public function addSituation(Situation $situation): self
    {
        if (!$this->situations->contains($situation)) {
            $this->situations[] = $situation;
        }

        return $this;
    }

    public function removeSituation(Situation $situation): self
    {
        $this->situations->removeElement($situation);

        return $this;
    }

    /**
     * @return Collection<int, Critere>
     */
    public function getCriteres(): Collection
    {
        return $this->criteres;
    }

    public function addCritere(Critere $critere): self
    {
        if (!$this->criteres->contains($critere)) {
            $this->criteres[] = $critere;
        }

        return $this;
    }

    public function removeCritere(Critere $critere): self
    {
        $this->criteres->removeElement($critere);

        return $this;
    }

    /**
     * @return Collection<int, Criticite>
     */
    public function getCriticites(): Collection
    {
        return $this->criticites;
    }

    public function addCriticite(Criticite $criticite): self
    {
        if (!$this->criticites->contains($criticite)) {
            $this->criticites[] = $criticite;
        }

        return $this;
    }

    public function removeCriticite(Criticite $criticite): self
    {
        $this->criticites->removeElement($criticite);

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

    public function getIsProprioAverti(): ?bool
    {
        return $this->isProprioAverti;
    }

    public function setIsProprioAverti(?bool $isProprioAverti): self
    {
        $this->isProprioAverti = $isProprioAverti;

        return $this;
    }

    /**
     * @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Utilisez la méthode @see getTypeCompositionLogement() afin de savoir le nombre de personnes qui vivent dans le logement
     */
    public function getNbAdultes(): ?string
    {
        return $this->nbAdultes;
    }

    /**
     * @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Utilisez la méthode @see setTypeCompositionLogement() afin de mettre à jour le nombre de personnes qui vivent dans le logement
     */
    public function setNbAdultes(?string $nbAdultes): self
    {
        $this->nbAdultes = $nbAdultes;

        return $this;
    }

    /**
     * @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Utilisez la méthode @see getTypeCompositionLogement() afin de savoir si des enfants de moins de 6 ans occupe le logement
     */
    public function getNbEnfantsM6(): ?string
    {
        return $this->nbEnfantsM6;
    }

    /**
     * @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Utilisez la méthode @see setTypeCompositionLogement() afin de mettre si des enfants de moins de 6 ans occupe le logement
     */
    public function setNbEnfantsM6(?string $nbEnfantsM6): self
    {
        $this->nbEnfantsM6 = $nbEnfantsM6;

        return $this;
    }

    /**
     * @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Il n'est plus utile de connaitre le nombre d'enfant de plus de 6 ans
     * Sera supprimé à la prochaine version
     */
    public function getNbEnfantsP6(): ?string
    {
        return $this->nbEnfantsP6;
    }

    /**
     * @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Il n'est plus utile de connaitre le nombre d'enfant de plus de 6 ans
     * Sera supprimé à la prochaine version
     */
    public function setNbEnfantsP6(?string $nbEnfantsP6): self
    {
        $this->nbEnfantsP6 = $nbEnfantsP6;

        return $this;
    }

    /**
     * @deprecated  Cette méthode gère l'addition du nombre de personnes avec les données obsolètes.
     * Sera supprimé à la prochaine version
     * (Utilisé par des fichiers twig)
     */
    public function getNbPersonsDeprecated(): string
    {
        $nbAdultes = str_replace('+', '', $this->nbAdultes ?? 0);
        $nbEnfantsM6 = str_replace('+', '', $this->getNbEnfantsM6() ?? 0);
        $nbEnfantsP6 = str_replace('+', '', $this->getNbEnfantsP6() ?? 0);

        $nbPersons = (int) $nbAdultes + (int) $nbEnfantsM6 + (int) $nbEnfantsP6;

        return (string) $nbPersons;
    }

    /**
     * @deprecated  Cette méthode gère l'addition du nombre de personnes avec les données obsolètes.
     * Sera supprimé à la prochaine version
     * (Utilisé par des fichiers twig)
     */
    public function getNbEnfantsDeprecated(): string
    {
        $nbEnfantsM6 = str_replace('+', '', $this->getNbEnfantsM6() ?? 0);
        $nbEnfantsP6 = str_replace('+', '', $this->getNbEnfantsP6() ?? 0);

        $nbEnfants = (int) $nbEnfantsM6 + (int) $nbEnfantsP6;

        return (string) $nbEnfants;
    }

    public function getIsAllocataire(): ?string
    {
        return $this->isAllocataire;
    }

    public function setIsAllocataire(?string $isAllocataire): self
    {
        $this->isAllocataire = $isAllocataire;

        return $this;
    }

    public function getNumAllocataire(): ?string
    {
        return $this->numAllocataire;
    }

    public function setNumAllocataire(?string $numAllocataire): self
    {
        $this->numAllocataire = $numAllocataire;

        return $this;
    }

    public function getNatureLogement(): ?string
    {
        return $this->natureLogement;
    }

    public function setNatureLogement(?string $natureLogement): self
    {
        $this->natureLogement = null !== $natureLogement ? mb_strtolower($natureLogement) : null;

        return $this;
    }

    public function getSuperficie(): ?float
    {
        return $this->superficie;
    }

    public function setSuperficie(?float $superficie): self
    {
        if (empty($superficie)) {
            $superficie = null;
        }

        $this->superficie = $superficie;

        return $this;
    }

    public function getLoyer(): ?float
    {
        return $this->loyer;
    }

    public function setLoyer(?float $loyer): self
    {
        $this->loyer = $loyer;

        return $this;
    }

    public function getIsBailEnCours(): ?bool
    {
        return $this->isBailEnCours;
    }

    public function setIsBailEnCours(?bool $isBailEnCours): self
    {
        $this->isBailEnCours = $isBailEnCours;

        return $this;
    }

    public function getIsLogementVacant(): ?bool
    {
        return $this->isLogementVacant;
    }

    public function setIsLogementVacant(?bool $isLogementVacant): self
    {
        $this->isLogementVacant = $isLogementVacant;

        return $this;
    }

    public function getDateEntree(): ?\DateTimeInterface
    {
        return $this->dateEntree;
    }

    public function setDateEntree(?\DateTimeInterface $dateEntree): self
    {
        $this->dateEntree = $dateEntree;

        return $this;
    }

    public function getTypeProprio(): ?ProprioType
    {
        return $this->typeProprio;
    }

    public function setTypeProprio(?ProprioType $typeProprio): self
    {
        $this->typeProprio = $typeProprio;

        return $this;
    }

    public function getNomProprio(): ?string
    {
        return $this->nomProprio;
    }

    public function setNomProprio(?string $nomProprio): self
    {
        $this->nomProprio = TrimHelper::safeTrim($nomProprio);

        return $this;
    }

    public function getDenominationProprio(): ?string
    {
        if ($this->bailleur) {
            return $this->bailleur->getName();
        }

        return $this->denominationProprio;
    }

    public function setDenominationProprio(?string $denominationProprio): self
    {
        $this->denominationProprio = $denominationProprio;

        return $this;
    }

    public function getAdresseProprio(): ?string
    {
        return $this->adresseProprio;
    }

    public function setAdresseProprio(?string $adresseProprio): self
    {
        $this->adresseProprio = $adresseProprio;

        return $this;
    }

    public function getTelProprio(): ?string
    {
        return $this->telProprio;
    }

    public function getTelProprioDecoded(?bool $national = false): ?string
    {
        return Phone::format($this->telProprio, $national);
    }

    public function setTelProprio(?string $telProprio): self
    {
        $this->telProprio = $telProprio;

        return $this;
    }

    public function getMailProprio(): ?string
    {
        return $this->mailProprio;
    }

    public function setMailProprio(?string $mailProprio): self
    {
        $this->mailProprio = TrimHelper::safeTrim($mailProprio);

        return $this;
    }

    public function getIsLogementSocial(): ?bool
    {
        return $this->isLogementSocial;
    }

    public function setIsLogementSocial(?bool $isLogementSocial): self
    {
        $this->isLogementSocial = $isLogementSocial;

        return $this;
    }

    public function getIsPreavisDepart(): ?bool
    {
        return $this->isPreavisDepart;
    }

    public function setIsPreavisDepart(?bool $isPreavisDepart): self
    {
        $this->isPreavisDepart = $isPreavisDepart;

        return $this;
    }

    public function getIsRelogement(): ?bool
    {
        return $this->isRelogement;
    }

    public function setIsRelogement(?bool $isRelogement): self
    {
        $this->isRelogement = $isRelogement;

        return $this;
    }

    public function getIsRefusIntervention(): ?bool
    {
        return $this->isRefusIntervention;
    }

    public function setIsRefusIntervention(?bool $isRefusIntervention): self
    {
        $this->isRefusIntervention = $isRefusIntervention;

        return $this;
    }

    /**
     * @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Sera supprimé à la prochaine version
     */
    public function getRaisonRefusIntervention(): ?string
    {
        return $this->raisonRefusIntervention;
    }

    /**
     * @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Sera supprimé à la prochaine version
     */
    public function setRaisonRefusIntervention(?string $raisonRefusIntervention): self
    {
        $this->raisonRefusIntervention = $raisonRefusIntervention;

        return $this;
    }

    public function getIsNotOccupant(): ?bool
    {
        return $this->isNotOccupant;
    }

    public function setIsNotOccupant(?bool $isNotOccupant): self
    {
        $this->isNotOccupant = $isNotOccupant;

        return $this;
    }

    public function getNomDeclarant(): ?string
    {
        return $this->nomDeclarant;
    }

    public function setNomDeclarant(?string $nomDeclarant): self
    {
        $this->nomDeclarant = TrimHelper::safeTrim($nomDeclarant);

        return $this;
    }

    public function getPrenomDeclarant(): ?string
    {
        return $this->prenomDeclarant;
    }

    public function setPrenomDeclarant(?string $prenomDeclarant): self
    {
        $this->prenomDeclarant = TrimHelper::safeTrim($prenomDeclarant);

        return $this;
    }

    public function getTelDeclarant(): ?string
    {
        return $this->telDeclarant;
    }

    public function getTelDeclarantDecoded(?bool $national = false): ?string
    {
        return Phone::format($this->telDeclarant, $national);
    }

    public function setTelDeclarant(?string $telDeclarant): self
    {
        $this->telDeclarant = $telDeclarant;

        return $this;
    }

    public function getMailDeclarant(): ?string
    {
        return $this->mailDeclarant;
    }

    public function setMailDeclarant(?string $mailDeclarant): self
    {
        $this->mailDeclarant = TrimHelper::safeTrim($mailDeclarant);

        return $this;
    }

    /** @return array<string> */
    public function getMailUsagers(): array
    {
        $usagers = [];
        if (!empty($this->getMailOccupant())) {
            $usagers[] = $this->getMailOccupant();
        }

        if (!empty($this->getMailDeclarant() && !\in_array($this->getMailDeclarant(), $usagers))) {
            $usagers[] = $this->getMailDeclarant();
        }

        return $usagers;
    }

    public function getStructureDeclarant(): ?string
    {
        return $this->structureDeclarant;
    }

    public function setStructureDeclarant(?string $structureDeclarant): self
    {
        $this->structureDeclarant = $structureDeclarant;

        return $this;
    }

    public function getNomOccupant(): ?string
    {
        return $this->nomOccupant;
    }

    public function setNomOccupant(?string $nomOccupant): self
    {
        $this->nomOccupant = TrimHelper::safeTrim($nomOccupant);

        return $this;
    }

    public function getPrenomOccupant(): ?string
    {
        return $this->prenomOccupant;
    }

    public function setPrenomOccupant(?string $prenomOccupant): self
    {
        $this->prenomOccupant = TrimHelper::safeTrim($prenomOccupant);

        return $this;
    }

    public function getTelOccupant(): ?string
    {
        return $this->telOccupant;
    }

    public function getTelOccupantDecoded(?bool $national = false): ?string
    {
        return Phone::format($this->telOccupant, $national);
    }

    public function setTelOccupant(?string $telOccupant): self
    {
        $this->telOccupant = $telOccupant;

        return $this;
    }

    public function getMailOccupant(): ?string
    {
        return $this->mailOccupant;
    }

    public function setMailOccupant(?string $mailOccupant): self
    {
        $this->mailOccupant = TrimHelper::safeTrim($mailOccupant);

        return $this;
    }

    public function getAdresseOccupant(): ?string
    {
        return $this->adresseOccupant;
    }

    public function setAdresseOccupant(string $adresseOccupant): self
    {
        $this->adresseOccupant = $adresseOccupant;

        return $this;
    }

    public function getCpOccupant(): ?string
    {
        return $this->cpOccupant;
    }

    public function setCpOccupant(?string $cpOccupant): self
    {
        $this->cpOccupant = $cpOccupant;

        return $this;
    }

    public function getVilleOccupant(): ?string
    {
        return $this->villeOccupant;
    }

    public function setVilleOccupant(string $villeOccupant): self
    {
        $this->villeOccupant = $villeOccupant;

        return $this;
    }

    public function getAddressCompleteOccupant(bool $withArrondisement = true): ?string
    {
        $ville = $withArrondisement ? $this->villeOccupant : CommuneHelper::getCommuneFromArrondissement($this->villeOccupant);

        return \sprintf(
            '%s %s %s',
            $this->adresseOccupant,
            $this->cpOccupant,
            $ville
        );
    }

    public function getBanIdOccupant(): ?string
    {
        return $this->banIdOccupant;
    }

    public function setBanIdOccupant(?string $banIdOccupant): self
    {
        $this->banIdOccupant = $banIdOccupant;

        return $this;
    }

    public function getNomAgence(): ?string
    {
        return $this->nomAgence;
    }

    public function setNomAgence(?string $nomAgence): self
    {
        $this->nomAgence = $nomAgence;

        return $this;
    }

    public function getDenominationAgence(): ?string
    {
        return $this->denominationAgence;
    }

    public function setDenominationAgence(?string $denominationAgence): self
    {
        $this->denominationAgence = $denominationAgence;

        return $this;
    }

    public function getPrenomAgence(): ?string
    {
        return $this->prenomAgence;
    }

    public function setPrenomAgence(?string $prenomAgence): self
    {
        $this->prenomAgence = $prenomAgence;

        return $this;
    }

    public function getAdresseAgence(): ?string
    {
        return $this->adresseAgence;
    }

    public function setAdresseAgence(?string $adresseAgence): self
    {
        $this->adresseAgence = $adresseAgence;

        return $this;
    }

    public function getCodePostalAgence(): ?string
    {
        return $this->codePostalAgence;
    }

    public function setCodePostalAgence(?string $codePostalAgence): self
    {
        $this->codePostalAgence = $codePostalAgence;

        return $this;
    }

    public function getVilleAgence(): ?string
    {
        return $this->villeAgence;
    }

    public function setVilleAgence(?string $villeAgence): self
    {
        $this->villeAgence = $villeAgence;

        return $this;
    }

    public function getTelAgence(): ?string
    {
        return $this->telAgence;
    }

    public function getTelAgenceDecoded(?bool $national = false): ?string
    {
        return Phone::format($this->telAgence, $national);
    }

    public function setTelAgence(?string $telAgence): self
    {
        $this->telAgence = $telAgence;

        return $this;
    }

    public function getTelAgenceSecondaire(): ?string
    {
        return $this->telAgenceSecondaire;
    }

    public function getTelAgenceSecondaireDecoded(?bool $national = false): ?string
    {
        return Phone::format($this->telAgenceSecondaire, $national);
    }

    public function setTelAgenceSecondaire(?string $telAgenceSecondaire): self
    {
        $this->telAgenceSecondaire = $telAgenceSecondaire;

        return $this;
    }

    public function getMailAgence(): ?string
    {
        return $this->mailAgence;
    }

    public function setMailAgence(?string $mailAgence): self
    {
        $this->mailAgence = $mailAgence;

        return $this;
    }

    public function getIsCguAccepted(): ?bool
    {
        return $this->isCguAccepted;
    }

    public function setIsCguAccepted(bool $isCguAccepted): self
    {
        $this->isCguAccepted = $isCguAccepted;

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

    public function getModifiedAt(): ?\DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(?\DateTimeImmutable $modifiedAt): self
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    public function getModifiedBy(): ?User
    {
        return $this->modifiedBy;
    }

    public function setModifiedBy(?User $modifiedBy): self
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getStatut(): ?SignalementStatus
    {
        return $this->statut;
    }

    public function setStatut(SignalementStatus $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getStatutLabel(): string
    {
        return ucfirst(SignalementStatus::getLabel($this->statut));
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    /** @return array<mixed> */
    public function getJsonContent(): ?array
    {
        return $this->jsonContent;
    }

    /** @param array<mixed> $jsonContent */
    public function setJsonContent(array $jsonContent): self
    {
        $this->jsonContent = $jsonContent;

        return $this;
    }

    /** @return array<mixed> */
    public function getGeoloc(): ?array
    {
        return $this->geoloc;
    }

    /** @param array<mixed> $geoloc */
    public function setGeoloc(array $geoloc): self
    {
        $this->geoloc = $geoloc;

        return $this;
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

    public function getMontantAllocation(): ?float
    {
        return $this->montantAllocation;
    }

    public function setMontantAllocation(?float $montantAllocation): self
    {
        $this->montantAllocation = $montantAllocation;

        return $this;
    }

    /**
     * @return Collection<int, Suivi>
     */
    public function getSuivis(): Collection
    {
        return $this->suivis;
    }

    public function addSuivi(Suivi $suivi): self
    {
        if (!$this->suivis->contains($suivi)) {
            $this->suivis[] = $suivi;
            $suivi->setSignalement($this);
        }

        return $this;
    }

    public function removeSuivi(Suivi $suivi): self
    {
        if ($this->suivis->removeElement($suivi)) {
            // set the owning side to null (unless already changed)
            if ($suivi->getSignalement() === $this) {
                $suivi->setSignalement(null);
            }
        }

        return $this;
    }

    public function getCodeProcedure(): ?string
    {
        return $this->codeProcedure;
    }

    public function setCodeProcedure(?string $codeProcedure): self
    {
        $this->codeProcedure = $codeProcedure;

        return $this;
    }

    /** @return array<mixed> */
    public function getAffectationStatusByPartner(): array
    {
        $result = [];
        foreach ($this->affectations as $affectation) {
            if (!array_keys($result, $affectation->getPartner()->getNom())) {
                if (!isset($result[$affectation->getPartner()->getNom()]['statut'])) {
                    $result[$affectation->getPartner()->getId()]['partner'] = $affectation->getPartner()->getNom();
                    $result[$affectation->getPartner()->getId()]['statuses'][] = $affectation->getStatut();
                }
            }
            $result[$affectation->getPartner()->getId()]['statut'] = max($result[$affectation->getPartner()->getId()]['statuses']);
        }

        return $result;
    }

    public function getEtageOccupant(): ?string
    {
        return $this->etageOccupant;
    }

    public function setEtageOccupant(?string $etageOccupant): self
    {
        $this->etageOccupant = $etageOccupant;

        return $this;
    }

    public function getEscalierOccupant(): ?string
    {
        return $this->escalierOccupant;
    }

    public function setEscalierOccupant(?string $escalierOccupant): self
    {
        $this->escalierOccupant = $escalierOccupant;

        return $this;
    }

    public function getNumAppartOccupant(): ?string
    {
        return $this->numAppartOccupant;
    }

    public function setNumAppartOccupant(?string $numAppartOccupant): self
    {
        $this->numAppartOccupant = $numAppartOccupant;

        return $this;
    }

    public function getAdresseAutreOccupant(): ?string
    {
        return $this->adresseAutreOccupant;
    }

    public function setAdresseAutreOccupant(?string $adresseAutreOccupant): self
    {
        $this->adresseAutreOccupant = $adresseAutreOccupant;

        return $this;
    }

    public function getComplementAdresseOccupant(bool $autre = true): string
    {
        $complement = '';
        if ($this->etageOccupant) {
            $complement .= 'étage '.$this->etageOccupant.', ';
        }
        if ($this->escalierOccupant) {
            $complement .= 'escalier '.$this->escalierOccupant.', ';
        }
        if ($this->numAppartOccupant) {
            $complement .= 'appartement '.$this->numAppartOccupant.', ';
        }
        if ($complement && (!$this->adresseAutreOccupant || !$autre)) {
            $complement = mb_substr($complement, 0, -2);
        }
        if ($this->adresseAutreOccupant && $autre) {
            $complement .= $this->adresseAutreOccupant;
        }

        return mb_strtoupper(mb_substr($complement, 0, 1)).mb_substr($complement, 1);
    }

    public function getInseeOccupant(): ?string
    {
        return $this->inseeOccupant;
    }

    public function setInseeOccupant(?string $inseeOccupant): self
    {
        $this->inseeOccupant = $inseeOccupant;

        return $this;
    }

    public function getManualAddressOccupant(): ?bool
    {
        return $this->manualAddressOccupant;
    }

    public function setManualAddressOccupant(?bool $manualAddressOccupant): self
    {
        $this->manualAddressOccupant = $manualAddressOccupant;

        return $this;
    }

    public function getCodeSuivi(): ?string
    {
        return $this->codeSuivi;
    }

    public function setCodeSuivi(string $codeSuivi): self
    {
        $this->codeSuivi = $codeSuivi;

        return $this;
    }

    public function getLienDeclarantOccupant(): ?string
    {
        return $this->lienDeclarantOccupant;
    }

    public function setLienDeclarantOccupant(?string $lienDeclarantOccupant): self
    {
        $this->lienDeclarantOccupant = $lienDeclarantOccupant;

        return $this;
    }

    public function getIsConsentementTiers(): ?bool
    {
        return $this->isConsentementTiers;
    }

    public function setIsConsentementTiers(?bool $isConsentementTiers): self
    {
        $this->isConsentementTiers = $isConsentementTiers;

        return $this;
    }

    public function getValidatedAt(): ?\DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTimeImmutable $validatedAt): self
    {
        $this->validatedAt = $validatedAt;

        return $this;
    }

    public function getIsRsa(): ?bool
    {
        return $this->isRsa;
    }

    public function setIsRsa(?bool $isRsa): self
    {
        $this->isRsa = $isRsa;

        return $this;
    }

    public function getAnneeConstruction(): ?string
    {
        return $this->anneeConstruction;
    }

    public function setAnneeConstruction(?string $anneeConstruction): self
    {
        $this->anneeConstruction = $anneeConstruction;

        return $this;
    }

    public function getTypeEnergieLogement(): ?string
    {
        return $this->typeEnergieLogement;
    }

    public function setTypeEnergieLogement(?string $typeEnergieLogement): self
    {
        $this->typeEnergieLogement = $typeEnergieLogement;

        return $this;
    }

    public function getOrigineSignalement(): ?string
    {
        return $this->origineSignalement;
    }

    public function setOrigineSignalement(?string $origineSignalement): self
    {
        $this->origineSignalement = $origineSignalement;

        return $this;
    }

    /**
     * @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Utilisez @see getProfileDeclarant() afin de connaitre la situation de l'occupant (LOCATAIRE, BAILLEUR_OCCUPANT)
     */
    public function getSituationOccupant(): ?string
    {
        return $this->situationOccupant;
    }

    /**
     * @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Utilisez @see setProfileDeclarant() afin d'affecter la situation de l'occupant (LOCATAIRE, BAILLEUR_OCCUPANT)
     */
    public function setSituationOccupant(?string $situationOccupant): self
    {
        $this->situationOccupant = $situationOccupant;

        return $this;
    }

    public function getSituationProOccupant(): ?string
    {
        return $this->situationProOccupant;
    }

    public function setSituationProOccupant(?string $situationProOccupant): self
    {
        $this->situationProOccupant = $situationProOccupant;

        return $this;
    }

    /**
     * @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Il n'est pas utile de connaitre les différentes naissances des occupants
     * Utilisez @see getDateNaissanceOccupant() afin de savoir la date de naissance de l'occupant (allocataire)
     * Utilisez @see InformationComplementaire::getInformationsComplementairesSituationOccupantsDateNaissance()
     * afin de savoir la date de naissance du bailleur
     */
    public function getNaissanceOccupants(): ?string
    {
        return $this->naissanceOccupants;
    }

    /**
     * @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Il n'est pas utile de connaitre les différentes naissances des occupants
     * Utilisez @see setDateNaissanceOccupant()
     * afin de savoir de mettere à jour la date de naissance de l'occupant (allocataire)
     * Utilisez @see InformationComplementaire::setInformationsComplementairesSituationOccupantsDateNaissance()
     * afin de savoir la date de naissance du bailleur
     */
    public function setNaissanceOccupants(?string $naissanceOccupants): self
    {
        $this->naissanceOccupants = $naissanceOccupants;

        return $this;
    }

    public function getIsLogementCollectif(): ?bool
    {
        return $this->isLogementCollectif;
    }

    public function setIsLogementCollectif(?bool $isLogementCollectif): self
    {
        $this->isLogementCollectif = $isLogementCollectif;

        return $this;
    }

    public function getIsConstructionAvant1949(): ?bool
    {
        return $this->isConstructionAvant1949;
    }

    public function setIsConstructionAvant1949(?bool $isConstructionAvant1949): self
    {
        $this->isConstructionAvant1949 = $isConstructionAvant1949;

        return $this;
    }

    public function getIsDiagSocioTechnique(): ?bool
    {
        return $this->isDiagSocioTechnique;
    }

    public function setIsDiagSocioTechnique(?bool $isDiagSocioTechnique): self
    {
        $this->isDiagSocioTechnique = $isDiagSocioTechnique;

        return $this;
    }

    public function getIsFondSolidariteLogement(): ?bool
    {
        return $this->isFondSolidariteLogement;
    }

    public function setIsFondSolidariteLogement(?bool $isFondSolidariteLogement): self
    {
        $this->isFondSolidariteLogement = $isFondSolidariteLogement;

        return $this;
    }

    public function getIsRisqueSurOccupation(): ?bool
    {
        return $this->isRisqueSurOccupation;
    }

    public function setIsRisqueSurOccupation(?bool $isRisqueSurOccupation): self
    {
        $this->isRisqueSurOccupation = $isRisqueSurOccupation;

        return $this;
    }

    public function getProprioAvertiAt(): ?\DateTimeImmutable
    {
        return $this->proprioAvertiAt;
    }

    public function setProprioAvertiAt(?\DateTimeImmutable $proprioAvertiAt): self
    {
        $this->proprioAvertiAt = $proprioAvertiAt;

        return $this;
    }

    public function getNomReferentSocial(): ?string
    {
        return $this->nomReferentSocial;
    }

    public function setNomReferentSocial(?string $nomReferentSocial): self
    {
        $this->nomReferentSocial = $nomReferentSocial;

        return $this;
    }

    public function getStructureReferentSocial(): ?string
    {
        return $this->structureReferentSocial;
    }

    public function setStructureReferentSocial(?string $structureReferentSocial): self
    {
        $this->structureReferentSocial = $structureReferentSocial;

        return $this;
    }

    public function getNumeroInvariant(): ?string
    {
        return $this->numeroInvariant;
    }

    public function setNumeroInvariant(?string $numeroInvariant): self
    {
        $this->numeroInvariant = $numeroInvariant;

        return $this;
    }

    public function getNumeroInvariantRial(): ?string
    {
        return $this->numeroInvariantRial;
    }

    public function setNumeroInvariantRial(?string $numeroInvariantRial): self
    {
        $this->numeroInvariantRial = $numeroInvariantRial;

        return $this;
    }

    public function getNbPiecesLogement(): ?int
    {
        return $this->nbPiecesLogement;
    }

    public function setNbPiecesLogement(?int $nbPiecesLogement): self
    {
        $this->nbPiecesLogement = $nbPiecesLogement;

        return $this;
    }

    /** @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Utilisez la méthode @see getTypeCompositionLogement() afin de savoir le nombre de pièces dans le logement
     */
    public function getNbChambresLogement(): ?int
    {
        return $this->nbChambresLogement;
    }

    /** @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Utilisez la méthode getTypeComposition() afin de savoir le nombre de pièces dans le logement
     */
    public function setNbChambresLogement(?int $nbChambresLogement): self
    {
        $this->nbChambresLogement = $nbChambresLogement;

        return $this;
    }

    public function getNbNiveauxLogement(): ?int
    {
        return $this->nbNiveauxLogement;
    }

    public function setNbNiveauxLogement(?int $nbNiveauxLogement): self
    {
        $this->nbNiveauxLogement = $nbNiveauxLogement;

        return $this;
    }

    public function getNbOccupantsLogement(): ?int
    {
        return $this->nbOccupantsLogement;
    }

    public function setNbOccupantsLogement(?int $nbOccupantsLogement): self
    {
        $this->nbOccupantsLogement = $nbOccupantsLogement;

        return $this;
    }

    /**
     * @return Collection<int, Affectation>
     */
    public function getAffectations(): Collection
    {
        return $this->affectations;
    }

    public function addAffectation(Affectation $affectation): self
    {
        if (!$this->affectations->contains($affectation)) {
            $this->affectations[] = $affectation;
            $affectation->setSignalement($this);
        }

        return $this;
    }

    public function removeAffectation(Affectation $affectation): self
    {
        if ($this->affectations->removeElement($affectation)) {
            // set the owning side to null (unless already changed)
            if ($affectation->getSignalement() === $this) {
                $affectation->setSignalement(null);
            }
        }

        return $this;
    }

    public function getMotifCloture(): ?MotifCloture
    {
        return $this->motifCloture;
    }

    public function setMotifCloture(?MotifCloture $motifCloture): self
    {
        $this->motifCloture = $motifCloture;

        return $this;
    }

    public function getMotifRefus(): ?MotifRefus
    {
        return $this->motifRefus;
    }

    public function setMotifRefus(?MotifRefus $motifRefus): self
    {
        $this->motifRefus = $motifRefus;

        return $this;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(?\DateTimeImmutable $closedAt): self
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    public function getTelOccupantBis(): ?string
    {
        return $this->telOccupantBis;
    }

    public function getTelOccupantBisDecoded(?bool $national = false): ?string
    {
        return Phone::format($this->telOccupantBis, $national);
    }

    public function setTelOccupantBis(?string $telOccupantBis): self
    {
        $this->telOccupantBis = $telOccupantBis;

        return $this;
    }

    /**
     * @return false|mixed|Suivi
     */
    public function getLastSuivi(): mixed
    {
        return $this->suivis->last();
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags->filter(function (Tag $tag) {
            return !$tag->getIsArchive();
        });
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
            $tag->addSignalement($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        if ($this->tags->removeElement($tag)) {
            $tag->removeSignalement($this);
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

    public function getIsImported(): ?bool
    {
        return $this->isImported;
    }

    public function setIsImported(?bool $isImported): self
    {
        $this->isImported = $isImported;

        return $this;
    }

    public function getClosedBy(): ?User
    {
        return $this->closedBy;
    }

    public function setClosedBy(?User $closedBy): self
    {
        $this->closedBy = $closedBy;

        return $this;
    }

    public function getLastSuiviAt(): ?\DateTimeImmutable
    {
        return $this->lastSuiviAt;
    }

    public function setLastSuiviAt(?\DateTimeImmutable $lastSuiviAt): self
    {
        $this->lastSuiviAt = $lastSuiviAt;

        return $this;
    }

    /**
     * @return Collection<int, SignalementQualification>
     */
    public function getSignalementQualifications(): Collection
    {
        return $this->signalementQualifications;
    }

    public function addSignalementQualification(SignalementQualification $signalementQualification): self
    {
        if (!$this->signalementQualifications->contains($signalementQualification)) {
            $this->signalementQualifications->add($signalementQualification);
            $signalementQualification->setSignalement($this);
        }

        return $this;
    }

    public function removeSignalementQualification(SignalementQualification $signalementQualification): self
    {
        if ($this->signalementQualifications->removeElement($signalementQualification)) {
            // set the owning side to null (unless already changed)
            if ($signalementQualification->getSignalement() === $this) {
                $signalementQualification->setSignalement(null);
            }
        }

        return $this;
    }

    public function hasNDE(): bool
    {
        foreach ($this->signalementQualifications as $qualification) {
            if ($qualification->isNDE()) {
                return true;
            }
        }

        return false;
    }

    public function getLastSuiviBy(): ?string
    {
        return $this->lastSuiviBy;
    }

    public function setLastSuiviBy(?string $lastSuiviBy): self
    {
        $this->lastSuiviBy = $lastSuiviBy;

        return $this;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(float $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getScoreLogement(): ?float
    {
        return $this->scoreLogement;
    }

    public function setScoreLogement(float $scoreLogement): self
    {
        $this->scoreLogement = $scoreLogement;

        return $this;
    }

    public function getScoreBatiment(): ?float
    {
        return $this->scoreBatiment;
    }

    public function setScoreBatiment(float $scoreBatiment): self
    {
        $this->scoreBatiment = $scoreBatiment;

        return $this;
    }

    public function getIsUsagerAbandonProcedure(): ?bool
    {
        return $this->isUsagerAbandonProcedure;
    }

    public function setIsUsagerAbandonProcedure(?bool $isUsagerAbandonProcedure): self
    {
        $this->isUsagerAbandonProcedure = $isUsagerAbandonProcedure;

        return $this;
    }

    /**
     * @return Collection<int, Intervention>
     */
    public function getInterventions(): Collection
    {
        return $this->interventions;
    }

    public function addIntervention(Intervention $intervention): self
    {
        if (!$this->interventions->contains($intervention)) {
            $this->interventions->add($intervention);
            $intervention->setSignalement($this);
        }

        return $this;
    }

    public function removeIntervention(Intervention $intervention): self
    {
        if ($this->interventions->removeElement($intervention)) {
            if ($intervention->getSignalement() === $this) {
                $intervention->setSignalement(null);
            }
        }

        return $this;
    }

    public function getDateNaissanceOccupant(): ?\DateTimeImmutable
    {
        return $this->dateNaissanceOccupant;
    }

    public function setDateNaissanceOccupant(?\DateTimeImmutable $dateNaissanceOccupant): self
    {
        $this->dateNaissanceOccupant = $dateNaissanceOccupant;

        return $this;
    }

    /**
     * @return Collection<int, File>
     */
    public function getPhotos(): Collection
    {
        return $this->files->filter(function (File $file) {
            return $file->isTypePhoto() && !$file->isTemp() && !$file->isIsWaitingSuivi();
        });
    }

    /**
     * @return Collection<int, File>
     */
    public function getDocuments(): Collection
    {
        return $this->files->filter(function (File $file) {
            return $file->isTypeDocument() && !$file->isTemp() && !$file->isIsWaitingSuivi();
        });
    }

    /**
     * @return Collection<int, File>
     */
    public function getFiles(?bool $isWaitingSuivi = false): Collection
    {
        return $this->files->filter(function (File $file) use ($isWaitingSuivi) {
            if ($file->isTemp()) {
                return false;
            }
            if ($file->isIsWaitingSuivi() && !$isWaitingSuivi) {
                return false;
            }

            return true;
        });
    }

    public function addFile(File $file): self
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setSignalement($this);
        }

        return $this;
    }

    public function removeFile(File $file): self
    {
        if ($this->files->removeElement($file)) {
            // set the owning side to null (unless already changed)
            if ($file->getSignalement() === $this) {
                $file->setSignalement(null);
            }
        }

        return $this;
    }

    public function isV2(): bool
    {
        return $this->createdFrom || $this->createdBy;
    }

    public function getCreatedFrom(): ?SignalementDraft
    {
        return $this->createdFrom;
    }

    public function setCreatedFrom(?SignalementDraft $createdFrom): self
    {
        $this->createdFrom = $createdFrom;

        return $this;
    }

    public function getProfileDeclarant(): ?ProfileDeclarant
    {
        return $this->profileDeclarant;
    }

    public function setProfileDeclarant(?ProfileDeclarant $profileDeclarant): self
    {
        $this->profileDeclarant = $profileDeclarant;

        return $this;
    }

    public function isTiersDeclarant(): bool
    {
        switch ($this->getProfileDeclarant()) {
            case ProfileDeclarant::SERVICE_SECOURS:
            case ProfileDeclarant::BAILLEUR:
            case ProfileDeclarant::TIERS_PRO:
            case ProfileDeclarant::TIERS_PARTICULIER:
                return true;
            case ProfileDeclarant::LOCATAIRE:
            case ProfileDeclarant::BAILLEUR_OCCUPANT:
            default:
                return false;
        }
    }

    public function getPrenomProprio(): ?string
    {
        return $this->prenomProprio;
    }

    public function setPrenomProprio(?string $prenomProprio): self
    {
        $this->prenomProprio = TrimHelper::safeTrim($prenomProprio);

        return $this;
    }

    public function getCodePostalProprio(): ?string
    {
        return $this->codePostalProprio;
    }

    public function setCodePostalProprio(?string $codePostalProprio): self
    {
        $this->codePostalProprio = $codePostalProprio;

        return $this;
    }

    public function getVilleProprio(): ?string
    {
        return $this->villeProprio;
    }

    public function setVilleProprio(?string $villeProprio): self
    {
        $this->villeProprio = $villeProprio;

        return $this;
    }

    public function getTelProprioSecondaire(): ?string
    {
        return $this->telProprioSecondaire;
    }

    public function getTelProprioSecondaireDecoded(?bool $national = false): ?string
    {
        return Phone::format($this->telProprioSecondaire, $national);
    }

    public function setTelProprioSecondaire(?string $telProprioSecondaire): self
    {
        $this->telProprioSecondaire = $telProprioSecondaire;

        return $this;
    }

    public function getTelDeclarantSecondaire(): ?string
    {
        return $this->telDeclarantSecondaire;
    }

    public function getTelDeclarantSecondaireDecoded(?bool $national = false): ?string
    {
        return Phone::format($this->telDeclarantSecondaire, $national);
    }

    public function setTelDeclarantSecondaire(?string $telDeclarantSecondaire): self
    {
        $this->telDeclarantSecondaire = $telDeclarantSecondaire;

        return $this;
    }

    public function getCiviliteOccupant(bool $raw = true): ?string
    {
        if (!$raw) {
            return match ($this->civiliteOccupant) {
                'mme' => 'Madame',
                'mr' => 'Monsieur',
                default => $this->civiliteOccupant,
            };
        }

        return $this->civiliteOccupant;
    }

    public function setCiviliteOccupant(?string $civiliteOccupant): self
    {
        $this->civiliteOccupant = $civiliteOccupant;

        return $this;
    }

    public function getTypeCompositionLogement(): ?TypeCompositionLogement
    {
        return $this->typeCompositionLogement;
    }

    public function setTypeCompositionLogement(?TypeCompositionLogement $typeCompositionLogement): self
    {
        $this->typeCompositionLogement = $typeCompositionLogement;

        return $this;
    }

    public function getSituationFoyer(): ?SituationFoyer
    {
        return $this->situationFoyer;
    }

    public function setSituationFoyer(?SituationFoyer $situationFoyer): self
    {
        $this->situationFoyer = $situationFoyer;

        return $this;
    }

    public function getInformationProcedure(): ?InformationProcedure
    {
        return $this->informationProcedure;
    }

    public function setInformationProcedure(?InformationProcedure $informationProcedure): self
    {
        $this->informationProcedure = $informationProcedure;

        return $this;
    }

    public function getInformationComplementaire(): ?InformationComplementaire
    {
        return $this->informationComplementaire;
    }

    public function setInformationComplementaire(?InformationComplementaire $informationComplementaire): self
    {
        $this->informationComplementaire = $informationComplementaire;

        return $this;
    }

    /**
     * @return Collection<int, DesordreCategorie>
     */
    public function getDesordreCategories(): Collection
    {
        return $this->desordreCategories;
    }

    public function addDesordreCategory(DesordreCategorie $desordreCategory): self
    {
        if (!$this->desordreCategories->contains($desordreCategory)) {
            $this->desordreCategories->add($desordreCategory);
        }

        return $this;
    }

    public function removeDesordreCategory(DesordreCategorie $desordreCategory): self
    {
        $this->desordreCategories->removeElement($desordreCategory);

        return $this;
    }

    public function removeAllDesordreCategory(): self
    {
        $this->desordreCategories->clear();

        return $this;
    }

    /**
     * @return Collection<int, DesordreCritere>
     */
    public function getDesordreCriteres(): Collection
    {
        return $this->desordreCriteres;
    }

    public function addDesordreCritere(DesordreCritere $desordreCritere): self
    {
        if (!$this->desordreCriteres->contains($desordreCritere)) {
            $this->desordreCriteres->add($desordreCritere);
        }

        return $this;
    }

    public function removeDesordreCritere(DesordreCritere $desordreCritere): self
    {
        $this->desordreCriteres->removeElement($desordreCritere);

        return $this;
    }

    public function removeAllDesordreCritere(): self
    {
        $this->desordreCriteres->clear();

        return $this;
    }

    /**
     * @return Collection<int, DesordrePrecision>
     */
    public function getDesordrePrecisions(): Collection
    {
        return $this->desordrePrecisions;
    }

    public function addDesordrePrecision(DesordrePrecision $desordrePrecision): self
    {
        if (!$this->desordrePrecisions->contains($desordrePrecision)) {
            $this->desordrePrecisions->add($desordrePrecision);
        }

        return $this;
    }

    public function removeDesordrePrecision(DesordrePrecision $desordrePrecision): self
    {
        $this->desordrePrecisions->removeElement($desordrePrecision);

        return $this;
    }

    public function removeAllDesordrePrecision(): self
    {
        $this->desordrePrecisions->clear();

        return $this;
    }

    public function __toString(): string
    {
        return $this->reference.' : '.$this->uuid;
    }

    public function hasQualificaton(Qualification $qualification): bool
    {
        /** @var SignalementQualification $signalementQualification */
        foreach ($this->signalementQualifications as $signalementQualification) {
            if ($signalementQualification->getQualification() === $qualification) {
                return true;
            }
        }

        return false;
    }

    public function getNomOccupantOrDeclarant(): string
    {
        return $this->nomOccupant ?? $this->nomDeclarant;
    }

    public function hasDesordreCategorie(DesordreCategorie $desordreCategorie): bool
    {
        return \in_array($desordreCategorie, $this->desordreCategories->toArray());
    }

    public function hasDesordreCritere(DesordreCritere $desordreCritere): bool
    {
        return \in_array($desordreCritere, $this->desordreCriteres->toArray());
    }

    public function hasDesordrePrecision(DesordrePrecision $desordrePrecision): bool
    {
        return \in_array($desordrePrecision, $this->desordrePrecisions->toArray());
    }

    public function getDesordreLabel(string $desordreSlug): string
    {
        /** @var DesordrePrecision $desordrePrecision */
        $desordrePrecision = $this->getDesordrePrecisions()->filter(function ($desordrePrecision) use ($desordreSlug) {
            return $desordrePrecision->getDesordrePrecisionSlug() == $desordreSlug;
        })->first();

        if ($desordrePrecision) {
            if ('' !== $desordrePrecision->getLabel()) {
                return $desordrePrecision->getLabel();
            }

            return $desordrePrecision->getDesordreCritere()->getLabelCritere();
        }

        /** @var DesordreCritere $desordreCritere */
        $desordreCritere = $this->getDesordreCriteres()->filter(function ($desordreCritere) use ($desordreSlug) {
            return $desordreCritere->getSlugCritere() == $desordreSlug;
        })->first();

        if ($desordreCritere) {
            return $desordreCritere->getLabelCritere();
        }

        /** @var DesordreCritere $desordreCritere */
        $desordreCritere = $this->getDesordreCriteres()->filter(function ($desordreCategorie) use ($desordreSlug) {
            return $desordreCategorie->getSlugCategorie() == $desordreSlug;
        })->first();

        if ($desordreCritere) {
            return $desordreCritere->getDesordreCategorie()->getLabel();
        }

        return $desordreSlug;
    }

    /** @return array<string> */
    public function getDesordreCritereSlugs(): array
    {
        return $this->getDesordreCriteres()->map(
            fn (DesordreCritere $desordreCritere) => $desordreCritere->getSlugCritere()
        )->toArray();
    }

    /** @return array<string> */
    public function getDesordrePrecisionSlugs(): array
    {
        return $this->getDesordrePrecisions()->map(
            fn (DesordrePrecision $desordrePrecision) => $desordrePrecision->getDesordrePrecisionSlug()
        )->toArray();
    }

    /** @return array<string> */
    public function getDesordreCategorieSlugs(): array
    {
        return $this->getDesordreCriteres()->map(
            fn (DesordreCritere $desordreCritere) => $desordreCritere->getSlugCategorie()
        )->toArray();
    }

    public function getBailleur(): ?Bailleur
    {
        return $this->bailleur;
    }

    public function setBailleur(?Bailleur $bailleur): self
    {
        $this->bailleur = $bailleur;

        return $this;
    }

    public function isLastSuiviIsPublic(): ?bool
    {
        return $this->lastSuiviIsPublic;
    }

    public function setLastSuiviIsPublic(?bool $lastSuiviIsPublic): self
    {
        $this->lastSuiviIsPublic = $lastSuiviIsPublic;

        return $this;
    }

    /** @return array<mixed> */
    public function getSynchroData(?string $key): ?array
    {
        if ($key) {
            return $this->synchroData[$key] ?? null;
        }

        return $this->synchroData;
    }

    /** @param array<mixed> $data */
    public function setSynchroData(array $data, string $key): self
    {
        $this->synchroData[$key] = $data;

        return $this;
    }

    public function hasSuiviUsagerPostCloture(): bool
    {
        $suiviPostCloture = $this->getSuivis()->filter(function (Suivi $suivi) {
            return Suivi::TYPE_USAGER_POST_CLOTURE === $suivi->getType();
        });
        if ($suiviPostCloture->isEmpty()) {
            return false;
        }

        return true;
    }

    public function getTimezone(): ?string
    {
        if (null === $this->getTerritory()) {
            return TimezoneProvider::TIMEZONE_EUROPE_PARIS;
        }

        return $this->getTerritory()->getTimezone();
    }

    /** @return array<mixed> */
    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }

    /** @return array<string> */
    public function getManyToManyFieldsToTrack(): array
    {
        return ['tags'];
    }

    public function getRnbIdOccupant(): ?string
    {
        return $this->rnbIdOccupant;
    }

    public function setRnbIdOccupant(?string $rnbIdOccupant): static
    {
        $this->rnbIdOccupant = $rnbIdOccupant;

        return $this;
    }

    public function getDebutDesordres(): ?DebutDesordres
    {
        return $this->debutDesordres;
    }

    public function setDebutDesordres(?DebutDesordres $debutDesordres): static
    {
        $this->debutDesordres = $debutDesordres;

        return $this;
    }

    public function getHasSeenDesordres(): ?bool
    {
        return $this->hasSeenDesordres;
    }

    public function setHasSeenDesordres(?bool $hasSeenDesordres): static
    {
        $this->hasSeenDesordres = $hasSeenDesordres;

        return $this;
    }

    public function getComCloture(): ?string
    {
        return $this->comCloture;
    }

    public function setComCloture(?string $comCloture): static
    {
        $this->comCloture = $comCloture;

        return $this;
    }

    public function getSignalementUsager(): ?SignalementUsager
    {
        return $this->signalementUsager;
    }

    public function getOccupantId(): ?int
    {
        return $this->getSignalementUsager()?->getOccupant()?->getId();
    }

    public function getDeclarantId(): ?int
    {
        return $this->getSignalementUsager()?->getDeclarant()?->getId();
    }

    /**
     * @return int[]
     */
    public function getUsagerIds(): array
    {
        return array_filter([$this->getOccupantId(), $this->getDeclarantId()]);
    }

    /**
     * @return User[]
     */
    public function getUsagers(): array
    {
        return array_filter([$this->getSignalementUsager()?->getOccupant(), $this->getSignalementUsager()?->getDeclarant()]);
    }
}
