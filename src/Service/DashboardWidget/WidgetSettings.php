<?php

namespace App\Service\DashboardWidget;

use App\Entity\User;
use Symfony\Component\Serializer\Attribute\Groups;

class WidgetSettings
{
    #[Groups('widget:read')]
    private ?string $firstname = null;
    #[Groups('widget:read')]
    private ?string $lastname = null;
    #[Groups('widget:read')]
    private ?string $roleLabel = null;
    #[Groups('widget:read')]
    private ?string $canSeeNDE = null;
    #[Groups('widget:read')]
    private ?int $partnerId = null;
    #[Groups('widget:read')]
    private ?string $partnerName = null;
    #[Groups('widget:read')]
    private ?string $territoryName = null;
    #[Groups('widget:read')]
    private array $territories = [];
    #[Groups('widget:read')]
    private array $partners = [];
    #[Groups('widget:read')]
    private array $communes = [];
    #[Groups('widget:read')]
    private array $epcis = [];
    #[Groups('widget:read')]
    private array $tags = [];
    private ?bool $hasSignalementImported = false;

    public function __construct(
        User $user,
        array $territories,
        bool $canSeeNDE,
        array $partners = [],
        array $communes = [],
        array $epcis = [],
        array $tags = [],
        bool $hasSignalementImported = false,
    ) {
        $this->firstname = $user->getPrenom();
        $this->lastname = $user->getNom();
        $this->roleLabel = $user->getRoleLabel();
        $this->canSeeNDE = (string) $canSeeNDE;
        $this->partnerId = $user->getPartner()->getId();
        $this->partnerName = $user->getPartner()->getNom();
        $this->territoryName = $user->getTerritory()?->getZip().'-'.$user->getTerritory()?->getName();
        $this->territories = $territories;
        $this->partners = $partners;
        $this->communes = $communes;
        $this->epcis = $epcis;
        $this->tags = $tags;
        $this->hasSignalementImported = $hasSignalementImported;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function getRoleLabel(): ?string
    {
        return $this->roleLabel;
    }

    public function getCanSeeNDE(): ?string
    {
        return $this->canSeeNDE;
    }

    public function getPartnerId(): ?int
    {
        return $this->partnerId;
    }

    public function getPartnerName(): ?string
    {
        return $this->partnerName;
    }

    public function getTerritoryName(): ?string
    {
        return $this->territoryName;
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

    public function getHasSignalementImported(): bool
    {
        return $this->hasSignalementImported;
    }
}
