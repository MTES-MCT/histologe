<?php

namespace App\Service\DashboardTabPanel\Kpi;

use App\Entity\User;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Symfony\Bundle\SecurityBundle\Security;

class TabCountKpiBuilder
{
    private ?TabCountKpi $tabCountKpi = null;

    /** @var array<int, mixed> */
    private array $territories = [];
    private ?int $territoryId = null;
    private ?string $mesDossiersMessagesUsagers = null;
    private ?string $mesDossiersAverifier = null;
    private ?string $mesDossiersActiviteRecente = null;
    private ?string $queryCommune = null;
    /** @var array<int, int> */
    private array $partners = [];

    public function __construct(
        private readonly TabCountKpiCalculatorInterface $tabCountKpiCalculator,
        private readonly Security $security,
    ) {
    }

    /**
     * @param array<int, mixed> $territories
     */
    public function setTerritories(array $territories, ?int $territoryId): self
    {
        $this->territories = $territories;
        $this->territoryId = $territoryId;

        return $this;
    }

    public function setMesDossiers(?string $mesDossiersMessagesUsagers, ?string $mesDossiersAverifier, ?string $mesDossiersActiviteRecente): self
    {
        $this->mesDossiersMessagesUsagers = $mesDossiersMessagesUsagers;
        $this->mesDossiersAverifier = $mesDossiersAverifier;
        $this->mesDossiersActiviteRecente = $mesDossiersActiviteRecente;

        return $this;
    }

    /**
     * @param array<int, int> $partners
     */
    public function setSearchAverifier(?string $queryCommune, ?array $partners): self
    {
        $this->queryCommune = $queryCommune;
        $this->partners = $partners;

        return $this;
    }

    public function withTabCountKpi(): static
    {
        $params = new TabQueryParameters(
            territoireId: $this->territoryId,
            partners: $this->partners,
            mesDossiersMessagesUsagers: $this->mesDossiersMessagesUsagers,
            mesDossiersAverifier: $this->mesDossiersAverifier,
            mesDossiersActiviteRecente: $this->mesDossiersActiviteRecente,
            queryCommune: $this->queryCommune
        );
        /** @var User $user */
        $user = $this->security->getUser();

        $countNouveauxDossiers = $this->tabCountKpiCalculator->countNouveauxDossiers($this->territories, $user);
        $countDossiersAFermer = $this->tabCountKpiCalculator->countDossiersAFermer($user, $params);
        $countDossiersMessagesUsagers = $this->tabCountKpiCalculator->countDossiersMessagesUsagers($user, $params);
        $countDossiersAVerifier = $this->tabCountKpiCalculator->countDossiersAVerifier($user, $params);

        $this->tabCountKpi = new TabCountKpi(
            countNouveauxDossiers: $countNouveauxDossiers->total(),
            countDossiersAFermer: $countDossiersAFermer->total(),
            countDossiersMessagesUsagers: $countDossiersMessagesUsagers->total(),
            countDossiersAVerifier: $countDossiersAVerifier->total(),
        );

        return $this;
    }

    public function build(): TabCountKpi
    {
        return $this->tabCountKpi;
    }
}
