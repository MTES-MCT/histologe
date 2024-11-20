<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\Enum\ZoneType;
use App\Repository\ZoneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ZoneRepository::class)]
#[ORM\UniqueConstraint(columns: ['name', 'territory_id'])]
#[UniqueEntity(
    fields: ['name', 'territory'],
    message: 'Ce nom est déjà utilisé sur le territoire.',
    errorPath: 'name',
)]
class Zone implements EntityHistoryInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $area = null;

    #[ORM\ManyToOne(inversedBy: 'zones')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Merci de sélectionner un territoire.')]
    private ?Territory $territory = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Merci de saisir un nom.')]
    private ?string $name = null;

    #[ORM\Column(
        type: 'string',
        enumType: ZoneType::class,
        options: ['comment' => 'Value possible enum ZoneType'])]
    #[Assert\NotBlank(message: 'Merci de choisir un type de zone.')]
    private ZoneType $type;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    /**
     * @var Collection<int, Partner>
     */
    #[ORM\ManyToMany(targetEntity: Partner::class, mappedBy: 'zones')]
    private Collection $partners;

    /**
     * @var Collection<int, Partner>
     */
    #[ORM\ManyToMany(targetEntity: Partner::class, mappedBy: 'excludedZones')]
    private Collection $excludedPartners;

    public function __construct()
    {
        $this->partners = new ArrayCollection();
        $this->excludedPartners = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArea(): ?string
    {
        return $this->area;
    }

    public function setArea(?string $area): static
    {
        $this->area = $area;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): ZoneType
    {
        return $this->type;
    }

    public function setType(ZoneType $type): self
    {
        $this->type = $type;

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

    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }

    /**
     * @return Collection<int, Partner>
     */
    public function getPartners(): Collection
    {
        return $this->partners;
    }

    public function addPartner(Partner $partner): static
    {
        if (!$this->partners->contains($partner)) {
            $this->partners->add($partner);
            $partner->addZone($this);
        }

        return $this;
    }

    public function removePartner(Partner $partner): static
    {
        if ($this->partners->removeElement($partner)) {
            $partner->removeZone($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Partner>
     */
    public function getExcludedPartners(): Collection
    {
        return $this->excludedPartners;
    }

    public function addExcludedPartner(Partner $excludedPartner): static
    {
        if (!$this->excludedPartners->contains($excludedPartner)) {
            $this->excludedPartners->add($excludedPartner);
            $excludedPartner->addExcludedZone($this);
        }

        return $this;
    }

    public function removeExcludedPartner(Partner $excludedPartner): static
    {
        if ($this->excludedPartners->removeElement($excludedPartner)) {
            $excludedPartner->removeExcludedZone($this);
        }

        return $this;
    }
}
