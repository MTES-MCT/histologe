<?php

namespace App\Entity;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Enum\HistoryEntryEvent;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Repository\ClubEventRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClubEventRepository::class)]
class ClubEvent implements EntityHistoryInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank()]
    private ?\DateTimeImmutable $dateEvent = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 50)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank()]
    #[Assert\Url()]
    #[Assert\Length(max: 255)]
    private ?string $url = null;

    #[ORM\Column]
    private array $userRoles = [];

    /**
     * @var PartnerType[]
     */
    #[ORM\Column]
    private array $partnerTypes = [];

    /**
     * @var Qualification[]
     */
    #[ORM\Column]
    private array $partnerCompetences = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateEvent(): ?\DateTimeImmutable
    {
        return $this->dateEvent;
    }

    public function setDateEvent(\DateTimeImmutable $dateEvent): static
    {
        $this->dateEvent = $dateEvent;

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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getUserRoles(): array
    {
        return $this->userRoles;
    }

    public function setUserRoles(array $userRoles): static
    {
        $this->userRoles = $userRoles;

        return $this;
    }

    /**
     * @return PartnerType[]
     */
    public function getPartnerTypes(): array
    {
        return array_filter(array_map(
            fn ($value) => $value instanceof PartnerType ? $value : PartnerType::tryFrom($value),
            $this->partnerTypes
        ));
    }

    /**
     * @param PartnerType[] $partnerTypes
     */
    public function setPartnerTypes(array $partnerTypes): static
    {
        $this->partnerTypes = $partnerTypes;

        return $this;
    }

    /**
     * @return Qualification[]
     */
    public function getPartnerCompetences(): array
    {
        return array_filter(array_map(
            fn ($value) => $value instanceof Qualification ? $value : Qualification::tryFrom($value),
            $this->partnerCompetences
        ));
    }

    /**
     * @param Qualification[] $partnerCompetences
     */
    public function setPartnerCompetences(array $partnerCompetences): static
    {
        $this->partnerCompetences = $partnerCompetences;

        return $this;
    }

    /** @return array<HistoryEntryEvent> */
    public function getHistoryRegisteredEvent(): array
    {
        return [HistoryEntryEvent::CREATE, HistoryEntryEvent::UPDATE, HistoryEntryEvent::DELETE];
    }
}
