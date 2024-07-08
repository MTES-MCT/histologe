<?php

namespace App\Entity;

use App\Entity\Enum\PartnerType;
use App\Repository\AutoAffectationRuleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AutoAffectationRuleRepository::class)]
class AutoAffectationRule
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_ARCHIVED = 'ARCHIVED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Territory::class, inversedBy: 'autoaffectationrules')]
    #[ORM\JoinColumn()]
    private Territory $territory;

    #[ORM\Column(type: 'string')]
    private string $status;

    #[ORM\Column(type: 'string', enumType: PartnerType::class)]
    private PartnerType $partnerType;

    #[ORM\Column(length: 255, type: 'string')]
    private string $profileDeclarant;

    #[ORM\Column(length: 255, options: ['comment' => 'Value possible all, partner_list or an array of code insee'])]
    private string $inseeToInclude;

    #[ORM\Column(nullable: true)]
    private ?array $inseeToExclude = null;

    #[ORM\Column(length: 32, options: ['comment' => 'Value possible all, prive or public'])]
    private string $parc;

    #[ORM\Column(length: 32, options: ['comment' => 'Value possible all, non, oui, caf or msa'])]
    private string $allocataire;

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
}
