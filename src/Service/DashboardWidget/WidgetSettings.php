<?php

namespace App\Service\DashboardWidget;

use App\Entity\User;

class WidgetSettings
{
    private ?string $firstname = null;
    private ?string $lastname = null;
    private ?string $roleLabel = null;
    private ?string $partnerName = null;
    private ?string $territoryName = null;

    private array $territories = [];

    public function __construct(User $user, array $territories = [])
    {
        $this->firstname = $user->getPrenom();
        $this->lastname = $user->getNom();
        $this->roleLabel = $user->getRoleLabel();
        $this->partnerName = $user->getPartner()->getNom();
        $this->territoryName = $user->getTerritory()?->getZip().'-'.$user->getTerritory()?->getName();
        $this->territories = $territories;
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
}
