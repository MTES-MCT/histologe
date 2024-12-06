<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\Qualification;
use App\Repository\AutoAffectationRuleRepository;
use App\Validator as AppAssert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AutoAffectationRuleRepository::class)]
class AutoAffectationRule implements EntityHistoryInterface
{
    public const string STATUS_ACTIVE = 'ACTIVE';
    public const string STATUS_ARCHIVED = 'ARCHIVED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Territory::class, inversedBy: 'autoAffectationRules')]
    #[ORM\JoinColumn()]
    #[Assert\NotBlank(message: 'Merci de choisir un territoire.')]
    private Territory $territory;

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

    #[ORM\Column(length: 255, options: ['comment' => 'Value possible all, partner_list or an array of code insee'])]
    #[Assert\NotBlank(message: 'Merci de renseigner les code insee des communes concernées.')]
    #[Assert\Length(max: 255)]
    #[AppAssert\InseeToInclude()]
    private string $inseeToInclude;

    #[ORM\Column(nullable: true, options: ['comment' => 'Value possible null or an array of code insee'])]
    #[AppAssert\InseeToExclude()]
    private ?array $inseeToExclude = null;

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

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true, enumType: Qualification::class)]
    private array $procedureSuspectee = [];

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

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPartnerType(): PartnerType
    {
        return $this->partnerType;
    }

    public function setPartnerType(PartnerType $partnerType): self
    {
        $this->partnerType = $partnerType;

        return $this;
    }

    public function getProfileDeclarant(): string
    {
        return $this->profileDeclarant;
    }

    public function setProfileDeclarant(string $profileDeclarant): self
    {
        $this->profileDeclarant = $profileDeclarant;

        return $this;
    }

    public function getInseeToInclude(): string
    {
        return $this->inseeToInclude;
    }

    public function setInseeToInclude(string $inseeToInclude): self
    {
        $this->inseeToInclude = $inseeToInclude;

        return $this;
    }

    public function getInseeToExclude(): ?array
    {
        return $this->inseeToExclude;
    }

    public function setInseeToExclude(?array $inseeToExclude): self
    {
        $this->inseeToExclude = $inseeToExclude;

        return $this;
    }

    public function getPartnerToExclude(): ?array
    {
        return $this->partnerToExclude;
    }

    public function setPartnerToExclude(?array $partnerToExclude): self
    {
        $this->partnerToExclude = $partnerToExclude;

        return $this;
    }

    public function getParc(): string
    {
        return $this->parc;
    }

    public function setParc(string $parc): self
    {
        $this->parc = $parc;

        return $this;
    }

    public function getAllocataire(): string
    {
        return $this->allocataire;
    }

    public function setAllocataire(string $allocataire): self
    {
        $this->allocataire = $allocataire;

        return $this;
    }

    public function getProcedureSuspectee(): ?array
    {
        return $this->procedureSuspectee;
    }

    public function setProcedureSuspectee(?array $procedureSuspectee): self
    {
        $this->procedureSuspectee = $procedureSuspectee;

        return $this;
    }

    public function hasProcedureSuspectee(Qualification $qualification): bool
    {
        return \in_array($qualification, $this->getProcedureSuspectee());
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
        if (\count($this->getProcedureSuspectee()) > 0) {
            $description .= ', ayant les procédures suspectées suivantes : ';
            foreach ($this->getProcedureSuspectee() as $procedure) {
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

        $description .= ' Elle s\'applique ';
        switch ($this->getInseeToInclude()) {
            case 'all':
                $description .= 'à tous les logements du territoire';
                break;
            case 'partner_list':
                $description .= 'aux logements situés dans le périmètre géographique du partenaire (codes insee et/ou zones)';
                break;
            default:
                $description .= 'aux logements situés dans les communes aux codes insee suivants : '.$this->getInseeToInclude();
                break;
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

    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }
}
