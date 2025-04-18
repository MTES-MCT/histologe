<?php

namespace App\Service\DashboardWidget;

use App\Entity\User;
use Symfony\Component\Serializer\Attribute\Groups;

class WidgetSettings
{
    #[Groups('widget-settings:read')]
    private ?string $firstname = null;
    #[Groups('widget-settings:read')]
    private ?string $lastname = null;
    #[Groups('widget-settings:read')]
    private ?string $avatarOrPlaceHolder = null;
    #[Groups('widget-settings:read')]
    private ?string $roleLabel = null;
    #[Groups('widget-settings:read')]
    private ?string $canSeeNDE = null;
    #[Groups('widget-settings:read')]
    private array $partnerIds = [];
    #[Groups('widget-settings:read')]
    private array $territories = [];
    #[Groups('widget-settings:read')]
    private array $partners = [];
    #[Groups('widget-settings:read')]
    private array $communes = [];
    #[Groups('widget-settings:read')]
    private array $epcis = [];
    #[Groups('widget-settings:read')]
    private array $tags = [];
    #[Groups('widget-settings:read')]
    private array $zones = [];
    #[Groups('widget-settings:read')]
    private ?bool $hasSignalementImported = false;
    #[Groups('widget-settings:read')]
    private ?bool $isMultiTerritoire = false;
    #[Groups('widget-settings:read')]
    private array $bailleursSociaux = [];

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

    public function getPartnerIds(): array
    {
        return $this->partnerIds;
    }

    public function getRoleLabel(): ?string
    {
        return $this->roleLabel;
    }

    public function getTerritories(): array
    {
        return $this->territories;
    }

    public function getPartners(): array
    {
        return $this->partners;
    }

    public function getCommunes(): array
    {
        return $this->communes;
    }

    public function getEpcis(): array
    {
        return $this->epcis;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

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

    public function getBailleursSociaux(): array
    {
        return $this->bailleursSociaux;
    }
}
