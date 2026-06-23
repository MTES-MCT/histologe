<?php

namespace App\Entity;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Enum\SuiviDelayedType;
use App\Repository\SuiviDelayedRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SuiviDelayedRepository::class)]
class SuiviDelayed
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: SuiviCategory::class)]
    private SuiviCategory $suiviCategory;

    #[ORM\Column(enumType: SuiviDelayedType::class)]
    private SuiviDelayedType $suiviDelayedType;

    /** @var array<mixed>|null $changes */
    #[ORM\Column(nullable: true)]
    private ?array $changes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Signalement $signalement;

    /**
     * @var Collection<int, File>
     */
    #[ORM\OneToMany(targetEntity: File::class, mappedBy: 'suiviDelayed')]
    private Collection $files;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->files = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSuiviCategory(): ?SuiviCategory
    {
        return $this->suiviCategory;
    }

    public function setSuiviCategory(SuiviCategory $suiviCategory): static
    {
        $this->suiviCategory = $suiviCategory;

        return $this;
    }

    public function getSuiviDelayedType(): ?SuiviDelayedType
    {
        return $this->suiviDelayedType;
    }

    public function setSuiviDelayedType(SuiviDelayedType $suiviDelayedType): static
    {
        $this->suiviDelayedType = $suiviDelayedType;

        return $this;
    }

    /** @return array<mixed>|null */
    public function getChanges(): ?array
    {
        return $this->changes;
    }

    /** @param array<mixed>|null $changes */
    public function setChanges(?array $changes): static
    {
        $this->changes = $changes;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
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

    public function getSignalement(): ?Signalement
    {
        return $this->signalement;
    }

    public function setSignalement(?Signalement $signalement): static
    {
        $this->signalement = $signalement;

        return $this;
    }

    /**
     * @return Collection<int, File>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(File $file): static
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setSuiviDelayed($this);
        }

        return $this;
    }

    public function removeFile(File $file): static
    {
        if ($this->files->removeElement($file)) {
            // set the owning side to null (unless already changed)
            if ($file->getSuiviDelayed() === $this) {
                $file->setSuiviDelayed(null);
            }
        }

        return $this;
    }
}
