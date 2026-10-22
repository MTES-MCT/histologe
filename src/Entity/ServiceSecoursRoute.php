<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\Enum\ServiceSecoursCategory;
use App\Repository\ServiceSecoursRouteRepository;
use App\Validator as AppAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ServiceSecoursRouteRepository::class)]
#[ORM\Table(name: 'service_secours_route')]
#[ORM\UniqueConstraint(name: 'unique_territory_category', columns: ['territory_id', 'category'])]
#[UniqueEntity('name')]
#[UniqueEntity(
    fields: ['territory', 'category'],
    message: 'Une configuration existe déjà pour ce territoire et cette catégorie.',
    errorPath: 'category',
)]
class ServiceSecoursRoute implements EntityHistoryInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Territory::class)]
    #[Assert\NotBlank]
    #[ORM\JoinColumn]
    private ?Territory $territory = null;

    #[ORM\Column(type: 'string', length: 50, enumType: ServiceSecoursCategory::class)]
    #[Assert\NotNull(message: 'Merci de choisir une catégorie.')]
    private ServiceSecoursCategory $category;

    #[ORM\Column(type: 'uuid', unique: true)]
    private string|Uuid $uuid;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $slug;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $email;

    #[ORM\Column(length: 20, nullable: true)]
    #[AppAssert\TelephoneFormat]
    private ?string $phone = null;

    /**
     * @var Collection<int, Signalement>
     */
    #[ORM\OneToMany(targetEntity: Signalement::class, mappedBy: 'serviceSecours')]
    private Collection $signalements;

    public function __construct()
    {
        $this->uuid = Uuid::v4();
        $this->signalements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCategory(): ?ServiceSecoursCategory
    {
        return $this->category;
    }

    public function setCategory(?ServiceSecoursCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getUuid(): string|Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        $slugger = new AsciiSlugger();
        $this->slug = $slugger->slug($name);

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function addSignalement(Signalement $signalement): static
    {
        if (!$this->signalements->contains($signalement)) {
            $this->signalements->add($signalement);
            $signalement->setServiceSecours($this);
        }

        return $this;
    }

    public function removeSignalement(Signalement $signalement): static
    {
        if ($this->signalements->removeElement($signalement)) {
            // set the owning side to null (unless already changed)
            if ($signalement->getServiceSecours() === $this) {
                $signalement->setServiceSecours(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Signalement>
     */
    public function getSignalements(): Collection
    {
        return $this->signalements;
    }

    /** @return array<HistoryEntryEvent> */
    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }
}
