<?php

namespace App\Entity;

use App\Entity\Enum\PartnerType;
use App\Repository\UserApiPermissionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserApiPermissionRepository::class)]
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
