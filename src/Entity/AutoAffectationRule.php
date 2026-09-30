<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Behaviour\TimestampableTrait;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\Qualification;
use App\Repository\AutoAffectationRuleRepository;
use App\Validator as AppAssert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: AutoAffectationRuleRepository::class)]
class AutoAffectationRule implements EntityHistoryInterface
{
    use TimestampableTrait;

    public const string STATUS_ACTIVE = 'ACTIVE';
    public const string STATUS_ARCHIVED = 'ARCHIVED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Territory::class, inversedBy: 'autoAffectationRules')]
    #[ORM\JoinColumn()]
    #[Assert\NotBlank(message: 'Merci de choisir un territoire.')]
    private ?Territory $territory;

    #[ORM\Column(type: 'string', options: ['comment' => 'Value possible ACTIVE or ARCHIVED'])]
    #[Assert\NotBlank(message: 'Merci de choisir un statut.')]
    #[Assert\Choice(
        choices: [self::STATUS_ACTIVE, self::STATUS_ARCHIVED],
        message: 'Choisissez une option valide: ACTIVE or ARCHIVED')]
    private string $status = self::STATUS_ACTIVE;

    #[ORM\Column(
        type: 'string',
        enumType: PartnerType::class,
        options: ['comment' => 'Value possible enum PartnerType'])]
    #[Assert\NotBlank(message: 'Merci de choisir un type de partenaire.')]
    #[AppAssert\ValidPartnerType]
    private PartnerType $partnerType;

    #[ORM\Column(
        type: 'string',
        length: 255,
        options: ['comment' => 'Value possible enum ProfileDeclarant or all, tiers or occupant'])]
    #[Assert\NotBlank(message: 'Merci de choisir un profil déclarant.')]
    #[Assert\Length(max: 255)]
    #[AppAssert\ValidProfileDeclarant()]
    private string $profileDeclarant;

    #[ORM\Column(length: 500, options: ['comment' => 'Value possible empty or an array of code insee'])]
    #[Assert\Length(max: 500)]
    #[AppAssert\InseeToInclude()]
    private string $inseeToInclude;

    /** @var array<string> $inseeToExclude */
    #[ORM\Column(nullable: true, options: ['comment' => 'Value possible null or an array of code insee'])]
    #[AppAssert\InseeToExclude()]
    private ?array $inseeToExclude = null;

    /** @var array<string> $partnerToExclude */
    #[ORM\Column(nullable: true, options: ['comment' => 'Value possible null or an array of partner ids'])]
    #[AppAssert\PartnerToExclude()]
    private ?array $partnerToExclude = null;

    #[ORM\Column(length: 32, options: ['comment' => 'Value possible all, non_renseigne, prive or public'])]
    #[Assert\NotBlank(message: 'Merci de renseigner le type de parc.')]
    #[Assert\Choice(
        choices: ['all', 'prive', 'public', 'non_renseigne'],
        message: 'Choisissez une option valide: all, non_renseigne, prive ou public')]
    private string $parc;

    #[ORM\Column(length: 32, options: ['comment' => 'Value possible all, non, oui, caf, msa or nsp'])]
    #[Assert\NotBlank(message: 'Merci de renseigner le profil d\'allocataire.')]
    #[Assert\Choice(
        choices: ['all', 'non', 'oui', 'caf', 'msa', 'nsp'],
        message: 'Choisissez une option valide: all, non, oui, caf, msa ou nsp')]
    private string $allocataire;

    #[ORM\Column(length: 32, options: ['comment' => 'Value possible all, oui, non or nsp'])]
    #[Assert\NotBlank(message: 'Merci de renseigner l\'accompagnement par un travailleur social.')]
    #[Assert\Choice(
        choices: ['all', 'oui', 'non', 'nsp'],
        message: 'Choisissez une option valide: all, oui, non ou nsp')]
    private string $accompagnementTravailleurSocial = 'all';

    #[ORM\Column(length: 32, options: ['comment' => 'Value possible all, oui, non or nsp'])]
    #[Assert\NotBlank(message: 'Merci de renseigner la demande de logement social.')]
    #[Assert\Choice(
        choices: ['all', 'oui', 'non', 'nsp'],
        message: 'Choisissez une option valide: all, oui, non ou nsp')]
    private string $demandeLogementSocial = 'all';

    /** @var array<string> $zoneToInclude */
    #[ORM\Column(nullable: true, options: ['comment' => 'Value possible null or an array of zone ids'])]
    #[AppAssert\ZoneIds()]
    private ?array $zoneToInclude = null;

    /** @var array<string> $zoneToExclude */
    #[ORM\Column(nullable: true, options: ['comment' => 'Value possible null or an array of zone ids'])]
    #[AppAssert\ZoneIds()]
    private ?array $zoneToExclude = null;

    /** @var list<Qualification> $proceduresSuspectees */
    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true, enumType: Qualification::class)]
    private ?array $proceduresSuspectees = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPartnerType(): PartnerType
    {
        return $this->partnerType;
    }

    public function setPartnerType(PartnerType $partnerType): static
    {
        $this->partnerType = $partnerType;

        return $this;
    }

    public function getProfileDeclarant(): string
    {
        return $this->profileDeclarant;
    }

    public function setProfileDeclarant(string $profileDeclarant): static
    {
        $this->profileDeclarant = $profileDeclarant;

        return $this;
    }

    public function getInseeToInclude(): string
    {
        return $this->inseeToInclude;
    }

    public function setInseeToInclude(string $inseeToInclude): static
    {
        $this->inseeToInclude = $inseeToInclude;

        return $this;
    }

    /** @return array<string> */
    public function getInseeToExclude(): ?array
    {
        return $this->inseeToExclude;
    }

    /** @param array<string> $inseeToExclude */
    public function setInseeToExclude(?array $inseeToExclude): static
    {
        $this->inseeToExclude = $inseeToExclude;

        return $this;
    }

    /** @return array<string> */
    public function getPartnerToExclude(): ?array
    {
        return $this->partnerToExclude;
    }

    /** @param array<string> $partnerToExclude */
    public function setPartnerToExclude(?array $partnerToExclude): static
    {
        $this->partnerToExclude = $partnerToExclude;

        return $this;
    }

    public function getParc(): string
    {
        return $this->parc;
    }

    public function setParc(string $parc): static
    {
        $this->parc = $parc;

        return $this;
    }

    public function getAllocataire(): string
    {
        return $this->allocataire;
    }

    public function setAllocataire(string $allocataire): static
    {
        $this->allocataire = $allocataire;

        return $this;
    }

    public function getAccompagnementTravailleurSocial(): string
    {
        return $this->accompagnementTravailleurSocial;
    }

    public function setAccompagnementTravailleurSocial(string $accompagnementTravailleurSocial): static
    {
        $this->accompagnementTravailleurSocial = $accompagnementTravailleurSocial;

        return $this;
    }

    public function getDemandeLogementSocial(): string
    {
        return $this->demandeLogementSocial;
    }

    public function setDemandeLogementSocial(string $demandeLogementSocial): static
    {
        $this->demandeLogementSocial = $demandeLogementSocial;

        return $this;
    }

    /** @return array<string> */
    public function getZoneToInclude(): ?array
    {
        return $this->zoneToInclude;
    }

    /** @param array<string> $zoneToInclude */
    public function setZoneToInclude(?array $zoneToInclude): static
    {
        $this->zoneToInclude = $zoneToInclude;

        return $this;
    }

    /** @return array<string> */
    public function getZoneToExclude(): ?array
    {
        return $this->zoneToExclude;
    }

    /** @param array<string> $zoneToExclude */
    public function setZoneToExclude(?array $zoneToExclude): static
    {
        $this->zoneToExclude = $zoneToExclude;

        return $this;
    }

    #[Assert\Callback]
    public function validateZonesNotIncludedAndExcluded(ExecutionContextInterface $context): void
    {
        foreach (array_intersect($this->zoneToExclude ?? [], $this->zoneToInclude ?? []) as $zoneId) {
            $context->buildViolation('La zone ID {{ id }} ne peut pas être à la fois incluse et exclue.')
                ->setParameter('{{ id }}', (string) $zoneId)
                ->atPath('zoneToExclude')
                ->addViolation();
        }
    }

    /** @return list<Qualification> */
    public function getProceduresSuspectees(): ?array
    {
        return $this->proceduresSuspectees;
    }

    /** @param list<Qualification> $proceduresSuspectees */
    public function setProceduresSuspectees(?array $proceduresSuspectees): static
    {
        $this->proceduresSuspectees = $proceduresSuspectees;

        return $this;
    }

    public function hasProcedureSuspectee(Qualification $qualification): bool
    {
        return \in_array($qualification, $this->getProceduresSuspectees());
    }

    public function getDescription(bool $isShort = true): string
    {
        $description = 'Règle d\'auto-affectation pour les partenaires '.$this->getPartnerType()->label();
        if (!$isShort && $this->getPartnerToExclude()) {
            $description .= ' (à l\'exclusion des partenaires '.implode(',', $this->getPartnerToExclude()).')';
        }
        $description .= ' du territoire '.$this->getTerritory()->getName();
        if ($isShort) {
            return $description;
        }

        $description .= ' concernant ';
        switch ($this->getParc()) {
            case 'prive':
                $description .= 'les logements du parc privé.';
                break;
            case 'public':
                $description .= 'les logements du parc public.';
                break;
            case 'non_renseigne':
                $description .= 'les logements de parc inconnu.';
                break;
            default:
                $description .= 'tous les logements.';
                break;
        }
        $description .= ' Cette règle concerne les signalements faits par ';
        switch ($this->getProfileDeclarant()) {
            case 'all':
                $description .= 'tous profils de déclarant';
                break;
            case 'tiers':
                $description .= 'un tiers';
                break;
            case 'occupant':
                $description .= 'un occupant';
                break;
            default:
                $description .= ProfileDeclarant::tryFrom($this->getProfileDeclarant())?->label();
                break;
        }
        if (\count($this->getProceduresSuspectees()) > 0) {
            $description .= ', ayant les procédures suspectées suivantes : ';
            foreach ($this->getProceduresSuspectees() as $procedure) {
                $description .= $procedure->label().' ';
            }
        }
        $description .= '. Elle concerne les foyers ';
        switch ($this->getAllocataire()) {
            case 'oui':
                $description .= 'allocataires.';
                break;
            case 'non':
                $description .= 'non-allocataires.';
                break;
            case 'caf':
                $description .= 'allocataires à la CAF.';
                break;
            case 'msa':
                $description .= 'allocataires à la MSA.';
                break;
            case 'nsp':
                $description .= 'dont on ne connait pas la situation d\'allocataire.';
                break;
            default:
                $description .= 'allocataires et non-allocataires.';
                break;
        }
        switch ($this->getAccompagnementTravailleurSocial()) {
            case 'oui':
                $description .= ' Elle concerne les foyers accompagnés par un travailleur social.';
                break;
            case 'non':
                $description .= ' Elle concerne les foyers non accompagnés par un travailleur social.';
                break;
            case 'nsp':
                $description .= ' Elle concerne les foyers dont on ne sait pas s\'ils sont accompagnés par un travailleur social.';
                break;
        }
        switch ($this->getDemandeLogementSocial()) {
            case 'oui':
                $description .= ' Elle concerne les foyers ayant fait une demande de logement social.';
                break;
            case 'non':
                $description .= ' Elle concerne les foyers n\'ayant pas fait de demande de logement social.';
                break;
            case 'nsp':
                $description .= ' Elle concerne les foyers dont on ne sait pas s\'ils ont fait une demande de logement social.';
                break;
        }

        $description .= ' Elle s\'applique ';
        switch ($this->getInseeToInclude()) {
            case null:
            case '':
                $description .= 'aux logements situés dans le périmètre géographique du partenaire (codes insee et/ou zones)';
                break;
            default:
                $description .= 'aux logements situés dans le périmètre géographique du partenaire (codes insee et/ou zones), limités aux codes insee suivants : '.$this->getInseeToInclude();
                break;
        }
        if ($this->getZoneToInclude()) {
            $description .= ', limités aux zones suivantes : '
            .implode(',', $this->getZoneToInclude());
        }
        if ($this->getZoneToExclude()) {
            $description .= ' à l\'exclusion des logements situés dans les zones suivantes : '
            .implode(',', $this->getZoneToExclude());
        }
        if ($this->getInseeToExclude()) {
            $description .= ' à l\'exclusion des logements situés dans les communes aux codes insee suivants : '
            .implode(',', $this->getInseeToExclude());
        } else {
            $description .= '.';
        }
        $description .= ' (Règle '.strtolower($this->getStatus()).')';

        return $description;
    }

    /** @return array<HistoryEntryEvent> */
    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }
}
