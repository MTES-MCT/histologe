<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\Enum\TypeArrete;
use App\Repository\ArreteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArreteRepository::class)]
class Arrete implements EntityHistoryInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $dateArrete;

    #[ORM\Column(enumType: TypeArrete::class)]
    private TypeArrete $typeArrete;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $syndic = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateMainLevee = null;

    #[ORM\ManyToOne(inversedBy: 'arretes')]
    #[ORM\JoinColumn(nullable: false)]
    private Address $address;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $importedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $createdBy = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $identifiantParcellaire = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateArrete(): \DateTimeImmutable
    {
        return $this->dateArrete;
    }

    public function setDateArrete(\DateTimeImmutable $dateArrete): static
    {
        $this->dateArrete = $dateArrete;

        return $this;
    }

    public function getTypeArrete(): TypeArrete
    {
        return $this->typeArrete;
    }

    public function setTypeArrete(TypeArrete $typeArrete): static
    {
        $this->typeArrete = $typeArrete;

        return $this;
    }

    public function getSyndic(): ?string
    {
        return $this->syndic;
    }

    public function setSyndic(?string $syndic): static
    {
        $this->syndic = $syndic;

        return $this;
    }

    public function isMainLevee(): bool
    {
        return null !== $this->dateMainLevee;
    }

    public function getDateMainLevee(): ?\DateTimeImmutable
    {
        return $this->dateMainLevee;
    }

    public function setDateMainLevee(?\DateTimeImmutable $dateMainLevee): static
    {
        $this->dateMainLevee = $dateMainLevee;

        return $this;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getImportedAt(): ?\DateTimeImmutable
    {
        return $this->importedAt;
    }

    public function setImportedAt(?\DateTimeImmutable $importedAt): static
    {
        $this->importedAt = $importedAt;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getIdentifiantParcellaire(): ?string
    {
        return $this->identifiantParcellaire;
    }

    public function setIdentifiantParcellaire(?string $identifiantParcellaire): static
    {
        $this->identifiantParcellaire = $identifiantParcellaire;

        return $this;
    }

    /** @return array<HistoryEntryEvent> */
    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }
}
