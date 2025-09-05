<?php

namespace App\Entity;

use App\Entity\Enum\PartnerType;
use App\Repository\UserApiPermissionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: UserApiPermissionRepository::class)]
#[UniqueEntity(['user', 'partner', 'territory', 'partnerType'], ignoreNull: false, errorPath: 'xx', message: 'Cette permission API existe déjà pour l\'utilisateur.')]
class UserApiPermission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'userApiPermissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    private ?Territory $territory = null;

    #[ORM\ManyToOne]
    private ?Partner $partner = null;

    #[ORM\Column(
        type: 'string',
        nullable: true,
        enumType: PartnerType::class,
        options: ['comment' => 'Value possible enum PartnerType']
    )]
    private ?PartnerType $partnerType = null;

    /**
     * @param ?array<mixed> $payload
     */
    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, ?array $payload): void
    {
        if (!$this->partner && !$this->partnerType && !$this->territory) {
            $message = 'Vous devez renseigner au moins un des champs suivant : partenaire, type de partenaire ou territoire.';
            $context->buildViolation($message)->atPath('partner')->addViolation();
            $context->buildViolation($message)->atPath('partnerType')->addViolation();
            $context->buildViolation($message)->atPath('territory')->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(?Territory $territory): static
    {
        $this->territory = $territory;

        return $this;
    }

    public function getPartner(): ?Partner
    {
        return $this->partner;
    }

    public function setPartner(?Partner $partner): static
    {
        $this->partner = $partner;

        return $this;
    }

    public function getPartnerType(): ?PartnerType
    {
        return $this->partnerType;
    }

    public function setPartnerType(?PartnerType $partnerType): static
    {
        $this->partnerType = $partnerType;

        return $this;
    }
}
