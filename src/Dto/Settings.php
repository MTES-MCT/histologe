<?php

namespace App\Dto;

use App\Entity\User;
use Symfony\Component\Serializer\Attribute\Groups;

class Settings
{
    #[Groups('settings:read')]
    private ?string $firstname = null;
    #[Groups('settings:read')]
    private ?string $lastname = null;
    #[Groups('settings:read')]
    private ?string $avatarOrPlaceHolder = null;
    #[Groups('settings:read')]
    private ?string $roleLabel = null;
    #[Groups('settings:read')]
    private ?string $canSeeNDE = null;
    /**
     * @var array<int, int> $partnerIds
     */
    #[Groups('settings:read')]
    private array $partnerIds = [];
    /**
     * @var array<int, mixed> $territories
     */
    #[Groups('settings:read')]
    private array $territories = [];
    /**
     * @var array<int, mixed>
     */
    #[Groups('settings:read')]
    private array $partners = [];
    /**
     * @var array<int, mixed>
     */
    #[Groups('settings:read')]
    private array $communes = [];
    /**
     * @var array<int, mixed>
     */
    #[Groups('settings:read')]
    private array $epcis = [];
    /**
     * @var array<int, mixed>
     */
    #[Groups('settings:read')]
    private array $tags = [];
    /**
     * @var array<int, mixed>
     */
    #[Groups('settings:read')]
    private array $zones = [];
    #[Groups('settings:read')]
    private ?bool $hasSignalementImported = false;
    #[Groups('settings:read')]
    private ?bool $isMultiTerritoire = false;
    /**
     * @var array<int, mixed>
     */
    #[Groups('settings:read')]
    private array $bailleursSociaux = [];

    /**
     * @param array<int, mixed> $territories
     * @param array<int, mixed> $partners
     * @param array<int, mixed> $communes
     * @param array<int, mixed> $epcis
     * @param array<int, mixed> $tags
     * @param array<int, mixed> $zones
     * @param array<int, mixed> $bailleursSociaux
     */
    public function __construct(
        User $user,
        array $territories,
        bool $canSeeNDE,
        array $partners = [],
        array $communes = [],
        array $epcis = [],
        array $tags = [],
        array $zones = [],
        bool $hasSignalementImported = false,
        array $bailleursSociaux = [],
        string $avatarOrPlaceHolder = '',
    ) {
        $this->firstname = $user->getPrenom();
        $this->lastname = $user->getNom();
        $this->avatarOrPlaceHolder = $avatarOrPlaceHolder;
        $this->roleLabel = $user->getRoleLabel();
        $this->canSeeNDE = (string) $canSeeNDE;
        $this->partnerIds = $user->getUserPartners()->map(fn ($userPartner) => $userPartner->getPartner()->getId())->toArray();
        $this->territories = $territories;
        $this->partners = $partners;
        $this->communes = $communes;
        $this->epcis = $epcis;
        $this->tags = $tags;
        $this->zones = $zones;
        $this->hasSignalementImported = $hasSignalementImported;
        $this->isMultiTerritoire = count($user->getPartnersTerritories()) > 1 ? true : false;
        $this->bailleursSociaux = $bailleursSociaux;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function getAvatarOrPlaceHolder(): string
    {
        return $this->avatarOrPlaceHolder;
    }

    public function getCanSeeNDE(): ?string
    {
        return $this->canSeeNDE;
    }

    /**
     * @return array<int, int>
     */
    public function getPartnerIds(): array
    {
        return $this->partnerIds;
    }

    public function getRoleLabel(): ?string
    {
        return $this->roleLabel;
    }

    /**
     * @return array<int, mixed>
     */
    public function getTerritories(): array
    {
        return $this->territories;
    }

    /**
     * @return array<int, mixed>
     */
    public function getPartners(): array
    {
        return $this->partners;
    }

    /**
     * @return array<int, mixed>
     */
    public function getCommunes(): array
    {
        return $this->communes;
    }

    /**
     * @return array<int, mixed>
     */
    public function getEpcis(): array
    {
        return $this->epcis;
    }

    /**
     * @return array<int, mixed>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return array<int, mixed>
     */
    public function getZones(): array
    {
        return $this->zones;
    }

    public function getHasSignalementImported(): bool
    {
        return $this->hasSignalementImported;
    }

    public function getIsMultiTerritoire(): ?bool
    {
        return $this->isMultiTerritoire;
    }

    /**
     * @return array<int, mixed>
     */
    public function getBailleursSociaux(): array
    {
        return $this->bailleursSociaux;
    }
}
