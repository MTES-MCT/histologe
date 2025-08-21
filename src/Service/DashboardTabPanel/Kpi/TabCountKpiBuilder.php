<?php

namespace App\Service\DashboardTabPanel\Kpi;

use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\SecurityBundle\Security;

class TabCountKpiBuilder
{
    private ?TabCountKpi $tabCountKpi = null;

    /** @var array<int, mixed> */
    private array $territories = [];
    private ?int $territoryId = null;
    private ?string $mesDossiersMessagesUsagers = null;
    private ?string $mesDossiersAverifier = null;

    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly SuiviRepository $suiviRepository,
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

    public function setMesDossiers(?string $mesDossiersMessagesUsagers, ?string $mesDossiersAverifier): self
    {
        $this->mesDossiersMessagesUsagers = $mesDossiersMessagesUsagers;
        $this->mesDossiersAverifier = $mesDossiersAverifier;

        return $this;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function withTabCountKpi(): static
    {
        $params = new TabQueryParameters(territoireId: $this->territoryId, mesDossiersMessagesUsagers: $this->mesDossiersMessagesUsagers, mesDossiersAverifier: $this->mesDossiersAverifier);
        /** @var User $user */
        $user = $this->security->getUser();
        if ($this->security->isGranted('ROLE_ADMIN_TERRITORY')) {
            $countNouveauxDossiers = $this->signalementRepository->countNouveauxDossiersKpi($this->territories);
        } else {
            $countNouveauxDossiers = $this->signalementRepository->countNouveauxDossiersKpi($this->territories, $user);
        }
        $countDossiersAFermer = $this->signalementRepository->countAllDossiersAferme($user, $this->territoryId);
        $countDossiersMessagesUsagers = $this->suiviRepository->countAllMessagesUsagers($user, $this->territoryId, $this->mesDossiersMessagesUsagers);
        $countDossiersAVerifier = $this->signalementRepository->countSignalementsSansSuiviPartenaireDepuis60Jours($user, $params);

        $this->tabCountKpi = new TabCountKpi(
            countNouveauxDossiers: $countNouveauxDossiers->total(),
            countDossiersAFermer: $countDossiersAFermer->total(),
            countDossiersMessagesUsagers: $countDossiersMessagesUsagers->total(),
            countDossiersAVerifier: $countDossiersAVerifier,
        );

        return $this;
    }

    public function build(): TabCountKpi
    {
        return $this->tabCountKpi;
    }
}
