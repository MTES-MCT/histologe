<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Repository\InAppCommunicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: InAppCommunicationRepository::class)]
class InAppCommunication implements EntityHistoryInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url()]
    #[Assert\Length(max: 255)]
    private ?string $url = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $urlTitle = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 255)]
    private string $communicationType;

    /**
     * @var array<string>
     */
    #[ORM\Column]
    private array $userRoles = [];

    /**
     * @var Collection<int, InAppCommunicationUser>
     */
    #[ORM\OneToMany(targetEntity: InAppCommunicationUser::class, mappedBy: 'inAppCommunication', orphanRemoval: true)]
    private Collection $inAppCommunicationUsers;

    public function __construct()
    {
        $this->inAppCommunicationUsers = new ArrayCollection();
    }

    #[Assert\Callback]
    public function validateCloseAffectation(ExecutionContextInterface $context): void
    {
        if (!$this->title && !$this->description) {
            $context->buildViolation('Veuillez préciser le titre ou la description.')
                ->atPath('title')
                ->addViolation();
        }
        if (!$this->url && $this->urlTitle) {
            $context->buildViolation('Veuillez préciser l\'URL.')
                ->atPath('url')
                ->addViolation();
        }
        if ($this->url && !$this->urlTitle) {
            $context->buildViolation('Veuillez préciser le libellé du lien.')
                ->atPath('urlTitle')
                ->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getUrlTitle(): ?string
    {
        return $this->urlTitle;
    }

    public function setUrlTitle(?string $urlTitle): static
    {
        $this->urlTitle = $urlTitle;

        return $this;
    }

    public function getCommunicationType(): string
    {
        return $this->communicationType;
    }

    public function setCommunicationType(string $communicationType): static
    {
        $this->communicationType = $communicationType;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getUserRoles(): array
    {
        return $this->userRoles;
    }

    /**
     * @param array<string> $userRoles
     */
    public function setUserRoles(array $userRoles): static
    {
        $this->userRoles = $userRoles;

        return $this;
    }

    /**
     * @return Collection<int, InAppCommunicationUser>
     */
    public function getInAppCommunicationUsers(): Collection
    {
        return $this->inAppCommunicationUsers;
    }

    public function addInAppCommunicationUser(InAppCommunicationUser $inAppCommunicationUser): static
    {
        if (!$this->inAppCommunicationUsers->contains($inAppCommunicationUser)) {
            $this->inAppCommunicationUsers->add($inAppCommunicationUser);
            $inAppCommunicationUser->setInAppCommunication($this);
        }

        return $this;
    }

    public function removeInAppCommunicationUser(InAppCommunicationUser $inAppCommunicationUser): static
    {
        $this->inAppCommunicationUsers->removeElement($inAppCommunicationUser);

        return $this;
    }

    /** @return array<HistoryEntryEvent> */
    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }
}
