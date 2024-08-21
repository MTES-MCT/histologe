<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[UniqueEntity(
    fields: ['label', 'territory', 'isArchive'],
    message: 'Ce nom d\'étiquette est déjà utilisé. Veuillez saisir une autre nom.',
    errorPath: 'label',
)]
class Tag implements EntityHistoryInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['widget-settings:read'])]
    private ?int $id = null;

    #[ORM\ManyToMany(targetEntity: Signalement::class, inversedBy: 'tags', cascade: ['persist'])]
    private Collection $signalement;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['widget-settings:read', 'history_entry:read'])]
    #[Assert\NotBlank(message: 'Merci de saisir un nom pour l\'étiquette.')]
    private ?string $label = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isArchive = false;

    #[ORM\ManyToOne(targetEntity: Territory::class, inversedBy: 'tags')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank()]
    #[Groups(['history_entry:read'])]
    private ?Territory $territory = null;

    public function __construct()
    {
        $this->signalement = new ArrayCollection();
        $this->isArchive = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|Signalement[]
     */
    public function getSignalement(): Collection
    {
        return $this->signalement;
    }

    public function addSignalement(Signalement $signalement): self
    {
        if (!$this->signalement->contains($signalement)) {
            $this->signalement[] = $signalement;
        }

        return $this;
    }

    public function removeSignalement(Signalement $signalement): self
    {
        $this->signalement->removeElement($signalement);

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getIsArchive(): ?bool
    {
        return $this->isArchive;
    }

    public function setIsArchive(bool $isArchive): self
    {
        $this->isArchive = $isArchive;

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

    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }
}
