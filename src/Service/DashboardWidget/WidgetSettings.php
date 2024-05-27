<?php

namespace App\Service\DashboardWidget;

use App\Entity\User;

class WidgetSettings
{
    private ?string $firstname = null;
    private ?string $lastname = null;
    private ?string $roleLabel = null;
    private ?string $canSeeNDE = null;
    private ?int $partnerId = null;
    private ?string $partnerName = null;
    private ?string $territoryName = null;

    private array $territories = [];
    private array $partners = [];
    private array $communes = [];
    private array $epcis = [];
    private array $tags = [];

    public function __construct(
        User $user,
        array $territories,
        bool $canSeeNDE,
        array $partners = [],
        array $communes = [],
        array $epcis = [],
        array $tags = [],
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
}
