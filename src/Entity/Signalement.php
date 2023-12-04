<?php

namespace App\Entity;

use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\MotifRefus;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Model\InformationComplementaire;
use App\Entity\Model\InformationProcedure;
use App\Entity\Model\SituationFoyer;
use App\Entity\Model\TypeCompositionLogement;
use App\Repository\SignalementRepository;
use App\Validator as AppAssert;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: SignalementRepository::class)]
#[ORM\Index(columns: ['statut'], name: 'idx_signalement_statut')]
#[ORM\Index(columns: ['created_at'], name: 'idx_signalement_created_at')]
class Signalement
{
    public const STATUS_NEED_VALIDATION = 1;
    public const STATUS_ACTIVE = 2;
    public const STATUS_NEED_PARTNER_RESPONSE = 3;
    public const STATUS_CLOSED = 6;
    public const STATUS_ARCHIVED = 7;
    public const STATUS_REFUSED = 8;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $uuid;

    #[ORM\Column(type: 'string', nullable: true, enumType: ProfileDeclarant::class)]
    private ?ProfileDeclarant $profileDeclarant = null;

    #[ORM\ManyToMany(targetEntity: Situation::class, inversedBy: 'signalements')]
    private $situations;

    #[ORM\ManyToMany(targetEntity: Critere::class, inversedBy: 'signalements')]
    private $criteres;

    #[ORM\ManyToMany(targetEntity: Criticite::class, inversedBy: 'signalements')]
    private $criticites;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private $details;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isProprioAverti;

    #[ORM\Column(type: 'string', nullable: true)]
    private $nbAdultes;

    #[ORM\Column(type: 'string', nullable: true)]
    private $nbEnfantsM6;

    #[ORM\Column(type: 'string', nullable: true)]
    private $nbEnfantsP6;

    #[ORM\Column(type: 'string', length: 3, nullable: true)]
    private $isAllocataire;

    #[ORM\Column(type: 'string', length: 25, nullable: true)]
    private $numAllocataire;

    #[ORM\Column(type: 'string', length: 15, nullable: true)]
    private $natureLogement;

    #[ORM\Column(type: 'string', length: 15, nullable: true)]
    private $typeLogement;

    #[ORM\Column(type: 'float', nullable: true)]
    private $superficie;

    #[ORM\Column(type: 'float', nullable: true)]
    private $loyer;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isBailEnCours;

    #[ORM\Column(type: 'date', nullable: true)]
    private $dateEntree;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $nomProprio;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $prenomProprio;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $adresseProprio;

    #[ORM\Column(type: 'string', length: 5, nullable: true)]
    private ?string $codePostalProprio;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $villeProprio;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    #[AppAssert\TelephoneFormat]
    private $telProprio;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    #[AppAssert\TelephoneFormat]
    private ?string $telProprioSecondaire;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $mailProprio;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isLogementSocial;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isPreavisDepart;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isRelogement;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isRefusIntervention;

    #[ORM\Column(type: 'text', nullable: true)]
    private $raisonRefusIntervention;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isNotOccupant;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $nomDeclarant;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private $prenomDeclarant;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    #[AppAssert\TelephoneFormat]
    private $telDeclarant;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    #[AppAssert\TelephoneFormat]
    private ?string $telDeclarantSecondaire;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Email(message: 'L\'adresse email du déclarant n\'est pas valide.')]
    private $mailDeclarant;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private $structureDeclarant;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $civiliteOccupant;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\NotBlank]
    private $nomOccupant;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\NotBlank]
    private $prenomOccupant;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    #[AppAssert\TelephoneFormat]
    private $telOccupant;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Email(message: 'L\'adresse email de l\'occupant n\'est pas valide.')]
    private $mailOccupant;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private $adresseOccupant;

    #[ORM\Column(type: 'string', length: 5, nullable: true)]
    #[Assert\NotBlank]
    private $cpOccupant;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private $villeOccupant;

    #[ORM\Column(type: 'boolean')]
    private $isCguAccepted;

    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $modifiedAt;

    #[ORM\Column(type: 'integer')]
    private $statut;

    #[ORM\Column(type: 'string', length: 100)]
    private $reference;

    #[ORM\Column(type: 'json')]
    private $jsonContent = [];

    #[ORM\Column(type: 'json')]
    private $geoloc = [];

    #[ORM\Column(type: 'float', nullable: true)]
    private $montantAllocation;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'signalementsModified')]
    private $modifiedBy;

    #[ORM\OneToMany(mappedBy: 'signalement', targetEntity: Suivi::class, orphanRemoval: true, cascade: ['persist'])]
    private $suivis;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSuiviAt = null;

    #[ORM\Column(nullable: true)]
    private ?string $lastSuiviBy = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $codeProcedure;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $etageOccupant;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $escalierOccupant;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $numAppartOccupant;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $adresseAutreOccupant;

    #[ORM\Column(type: 'json', nullable: true)]
    private $modeContactProprio = [];

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $inseeOccupant;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $codeSuivi;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $lienDeclarantOccupant;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isConsentementTiers;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $validatedAt;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isRsa;

    #[ORM\Column(type: 'string', nullable: true)]
    private $anneeConstruction;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $typeEnergieLogement;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $origineSignalement;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $situationOccupant;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $situationProOccupant;

    #[ORM\Column(type: 'string', nullable: true)]
    private $naissanceOccupants;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isLogementCollectif;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isConstructionAvant1949;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isDiagSocioTechnique;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isFondSolidariteLogement;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isRisqueSurOccupation;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $proprioAvertiAt;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $nomReferentSocial;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $StructureReferentSocial;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $numeroInvariant;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $nbPiecesLogement;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $nbChambresLogement;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $nbNiveauxLogement;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $nbOccupantsLogement;

    #[ORM\OneToMany(mappedBy: 'signalement', targetEntity: Affectation::class, cascade: ['persist'], orphanRemoval: true)]
    private $affectations;

    #[ORM\Column(type: 'string', enumType: MotifCloture::class, nullable: true)]
    private ?MotifCloture $motifCloture;

    #[ORM\Column(type: 'string', enumType: MotifRefus::class, nullable: true)]
    private ?MotifRefus $motifRefus;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $closedAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'signalementsClosed')]
    private $closedBy;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    #[AppAssert\TelephoneFormat]
    private $telOccupantBis;

    #[ORM\ManyToMany(targetEntity: Tag::class, mappedBy: 'signalement', cascade: ['persist'])]
    private $tags;

    #[ORM\ManyToOne(targetEntity: Territory::class, inversedBy: 'signalements')]
    #[ORM\JoinColumn(nullable: true)]
    private $territory;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $isImported;

    #[ORM\OneToMany(mappedBy: 'signalement', targetEntity: SignalementQualification::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $signalementQualifications;

    #[ORM\Column]
    private ?float $score = null;

    #[ORM\OneToMany(mappedBy: 'signalement', targetEntity: Intervention::class, orphanRemoval: true)]
    private Collection $interventions;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isUsagerAbandonProcedure;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateNaissanceOccupant = null;

    #[ORM\OneToMany(mappedBy: 'signalement', targetEntity: File::class, cascade: ['persist'])]
    private Collection $files;

    #[ORM\ManyToOne(inversedBy: 'signalements')]
    private ?SignalementDraft $createdFrom = null;

    #[ORM\Column(type: 'type_composition_logement', nullable: true)]
    private ?TypeCompositionLogement $typeCompositionLogement;

    #[ORM\Column(type: 'situation_foyer', nullable: true)]
    private ?SituationFoyer $situationFoyer;

    #[ORM\Column(type: 'information_procedure', nullable: true)]
    private ?InformationProcedure $informationProcedure;

    #[ORM\Column(type: 'information_complementaire', nullable: true)]
    private ?InformationComplementaire $informationComplementaire;

    #[ORM\ManyToMany(targetEntity: DesordreCategorie::class, mappedBy: 'Signalement')]
    private Collection $desordreCategories;

    #[ORM\ManyToMany(targetEntity: DesordreCritere::class, mappedBy: 'Signalement')]
    private Collection $desordreCriteres;

    #[ORM\ManyToMany(targetEntity: DesordrePrecision::class, mappedBy: 'signalement')]
    private Collection $desordrePrecisions;

    public function __construct()
    {
        $this->situations = new ArrayCollection();
        $this->criteres = new ArrayCollection();
        $this->criticites = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->statut = self::STATUS_NEED_VALIDATION;
        $this->uuid = Uuid::v4();
        $this->suivis = new ArrayCollection();
        $this->score = 0;
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
            $context->buildViolation('Vous devez renseigner au moins une adresse email pour le déclarant ou l\'occupant.')
                ->atPath('mailDeclarant')
                ->atPath('mailOccupant')
                ->addViolation();
        }
        if ($this->mailDeclarant && $this->mailOccupant && $this->mailDeclarant === $this->mailOccupant) {
            $context->buildViolation('les adresses emails du déclarant et de l\'occupant sont identiques (laisser l\'adresse vide si l\'occupant n\'en dispose pas).')
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
     * @return Collection|Situation[]
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
     * @return Collection|Critere[]
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
     * @return Collection|Criticite[]
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

    public function setIsProprioAverti(bool|null $isProprioAverti): self
    {
        $this->isProprioAverti = $isProprioAverti;

        return $this;
    }

    /**
     * @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Utilisez la méthode @see getTypeCompositionLogement() afin de savoir le nombre de personnes qui vivent dans le logement
     */
    public function getNbAdultes()
    {
        return $this->nbAdultes;
    }

    /**
     * @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Utilisez la méthode @see setTypeCompositionLogement() afin de mettre à jour le nombre de personnes qui vivent dans le logement
     */
    public function setNbAdultes($nbAdultes): self
    {
        $this->nbAdultes = $nbAdultes;

        return $this;
    }

    /**
     * @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Utilisez la méthode @see getTypeCompositionLogement() afin de savoir si des enfants de moins de 6 ans occupe le logement
     */
    public function getNbEnfantsM6()
    {
        return $this->nbEnfantsM6;
    }

    /**
     * @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Utilisez la méthode @see setTypeCompositionLogement() afin de mettre si des enfants de moins de 6 ans occupe le logement
     */
    public function setNbEnfantsM6($nbEnfantsM6): self
    {
        $this->nbEnfantsM6 = $nbEnfantsM6;

        return $this;
    }

    /**
     * @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Il n'est plus utile de connaitre le nombre d'enfant de plus de 6 ans
     * Sera supprimé à la prochaine version
     */
    public function getNbEnfantsP6()
    {
        return $this->nbEnfantsP6;
    }

    /**
     * @deprecated  Cette méthode est obsolete et ne doit plus être utilisé dans le cadre du nouveau formulaire
     * Il n'est plus utile de connaitre le nombre d'enfant de plus de 6 ans
     * Sera supprimé à la prochaine version
     */
    public function setNbEnfantsP6($nbEnfantsP6): self
    {
        $this->nbEnfantsP6 = $nbEnfantsP6;

        return $this;
    }

    public function getIsAllocataire(): ?string
    {
        return $this->isAllocataire;
    }

    public function setIsAllocataire(?string $isAllocataire)
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

    public function getTypeLogement(): ?string
    {
        return $this->typeLogement;
    }

    public function setTypeLogement(?string $typeLogement): self
    {
        $this->typeLogement = mb_strtoupper($typeLogement);

        return $this;
    }

    public function getSuperficie(): ?float
    {
        return $this->superficie;
    }

    public function setSuperficie(?float $superficie): self
    {
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

    public function getDateEntree(): ?DateTimeInterface
    {
        return $this->dateEntree;
    }

    public function setDateEntree(?DateTimeInterface $dateEntree): self
    {
        $this->dateEntree = $dateEntree;

        return $this;
    }

    public function getNomProprio(): ?string
    {
        return $this->nomProprio;
    }

    public function setNomProprio(?string $nomProprio): self
    {
        $this->nomProprio = $nomProprio;

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
        return $this->getTelGenerique($this->telProprio, $national);
    }

    private function getTelGenerique(?string $tel, bool $national = false)
    {
        if (!$tel) {
            return $tel;
        }
        $telDecoded = json_decode($tel);
        $phoneNumberUtil = PhoneNumberUtil::getInstance();
        if ($telDecoded && isset($telDecoded->phone_number) && isset($telDecoded->country_code)) {
            $phoneNumberParsed = $phoneNumberUtil->parse($telDecoded->phone_number, substr($telDecoded->country_code, 0, 2));
        } else {
            $phoneNumberParsed = $phoneNumberUtil->parse($tel, 'FR');
        }
        if ($national) {
            return str_replace(' ', '', $phoneNumberUtil->format($phoneNumberParsed, \libphonenumber\PhoneNumberFormat::NATIONAL));
        }

        return $phoneNumberUtil->format($phoneNumberParsed, \libphonenumber\PhoneNumberFormat::E164);
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
        $this->mailProprio = $mailProprio;

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
        $this->nomDeclarant = $nomDeclarant;

        return $this;
    }

    public function getPrenomDeclarant(): ?string
    {
        return $this->prenomDeclarant;
    }

    public function setPrenomDeclarant(?string $prenomDeclarant): self
    {
        $this->prenomDeclarant = $prenomDeclarant;

        return $this;
    }

    public function getTelDeclarant(): ?string
    {
        return $this->telDeclarant;
    }

    public function getTelDeclarantDecoded(?bool $national = false): ?string
    {
        return $this->getTelGenerique($this->telDeclarant, $national);
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
        $this->mailDeclarant = $mailDeclarant;

        return $this;
    }

    public function getMailUsagers(): array
    {
        $usagers = [];
        if (!empty($this->getMailOccupant())) {
            $usagers[] = $this->getMailOccupant();
        }

        if (!empty($this->getMailDeclarant())) {
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
        $this->nomOccupant = $nomOccupant;

        return $this;
    }

    public function getPrenomOccupant(): ?string
    {
        return $this->prenomOccupant;
    }

    public function setPrenomOccupant(?string $prenomOccupant): self
    {
        $this->prenomOccupant = $prenomOccupant;

        return $this;
    }

    public function getTelOccupant(): ?string
    {
        return $this->telOccupant;
    }

    public function getTelOccupantDecoded(?bool $national = false): ?string
    {
        return $this->getTelGenerique($this->telOccupant, $national);
    }

    public function setTelOccupant($telOccupant): self
    {
        $this->telOccupant = $telOccupant;

        return $this;
    }

    public function getMailOccupant(): ?string
    {
        return $this->mailOccupant;
    }

    public function setMailOccupant($mailOccupant): self
    {
        $this->mailOccupant = $mailOccupant;

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

    public function getAddressCompleteOccupant(): ?string
    {
        return sprintf('%s %s %s',
            $this->adresseOccupant,
            $this->cpOccupant,
            $this->villeOccupant
        );
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

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getModifiedAt(): ?DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(?DateTimeImmutable $modifiedAt): self
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

    public function getStatut(): ?int
    {
        return $this->statut;
    }

    public function setStatut(int $statut): self
    {
        $this->statut = $statut;

        return $this;
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

    public function getJsonContent(): ?array
    {
        return $this->jsonContent;
    }

    public function setJsonContent(array $jsonContent): self
    {
        $this->jsonContent = $jsonContent;

        return $this;
    }

    public function getGeoloc(): ?array
    {
        return $this->geoloc;
    }

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

    public function getModeContactProprio(): ?array
    {
        return $this->modeContactProprio;
    }

    public function setModeContactProprio(?array $modeContactProprio): self
    {
        $this->modeContactProprio = $modeContactProprio;

        return $this;
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

    public function getCodeSuivi(): ?string
    {
        return $this->codeSuivi;
    }

    public function setCodeSuivi(?string $codeSuivi): self
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

    public function getValidatedAt(): ?DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(DateTimeImmutable|null $validatedAt): self
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

    public function getSituationOccupant(): ?string
    {
        return $this->situationOccupant;
    }

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

    public function getProprioAvertiAt(): ?DateTimeImmutable
    {
        return $this->proprioAvertiAt;
    }

    public function setProprioAvertiAt(?DateTimeImmutable $proprioAvertiAt): self
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
        return $this->StructureReferentSocial;
    }

    public function setStructureReferentSocial(?string $StructureReferentSocial): self
    {
        $this->StructureReferentSocial = $StructureReferentSocial;

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
     * @return Collection|Affectation[]
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

    public function getClosedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(?DateTimeImmutable $closedAt): self
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
        return $this->getTelGenerique($this->telOccupantBis, $national);
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
     * @return Collection|Tag[]
     */
    public function getTags(): Collection
    {
        return $this->tags;
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

    public function getLastSuiviAt(): ?DateTimeImmutable
    {
        return $this->lastSuiviAt;
    }

    public function setLastSuiviAt(?DateTimeImmutable $lastSuiviAt): self
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
     * @return Collection|Intervention[]
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

    public function getDateNaissanceOccupant(): ?DateTimeImmutable
    {
        return $this->dateNaissanceOccupant;
    }

    public function setDateNaissanceOccupant(?DateTimeImmutable $dateNaissanceOccupant): self
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
            return 'photo' === $file->getFiletype();
        });
    }

    /**
     * @return Collection<int, File>
     */
    public function getDocuments(): Collection
    {
        return $this->files->filter(function (File $file) {
            return 'document' === $file->getFiletype();
        });
    }

    /**
     * @return Collection<int, File>
     */
    public function getFiles(): Collection
    {
        return $this->files;
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

    public function getPrenomProprio(): ?string
    {
        return $this->prenomProprio;
    }

    public function setPrenomProprio(?string $prenomProprio): self
    {
        $this->prenomProprio = $prenomProprio;

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
        return $this->getTelGenerique($this->telProprioSecondaire, $national);
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
        return $this->getTelGenerique($this->telDeclarantSecondaire, $national);
    }

    public function setTelDeclarantSecondaire(?string $telDeclarantSecondaire): self
    {
        $this->telDeclarantSecondaire = $telDeclarantSecondaire;

        return $this;
    }

    public function getCiviliteOccupant(): ?string
    {
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
            $desordreCategory->addSignalement($this);
        }

        return $this;
    }

    public function removeDesordreCategory(DesordreCategorie $desordreCategory): self
    {
        if ($this->desordreCategories->removeElement($desordreCategory)) {
            $desordreCategory->removeSignalement($this);
        }

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
            $desordreCritere->addSignalement($this);
        }

        return $this;
    }

    public function removeDesordreCritere(DesordreCritere $desordreCritere): self
    {
        if ($this->desordreCriteres->removeElement($desordreCritere)) {
            $desordreCritere->removeSignalement($this);
        }

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
            $desordrePrecision->addSignalement($this);
        }

        return $this;
    }

    public function removeDesordrePrecision(DesordrePrecision $desordrePrecision): self
    {
        if ($this->desordrePrecisions->removeElement($desordrePrecision)) {
            $desordrePrecision->removeSignalement($this);
        }

        return $this;
    }
}
