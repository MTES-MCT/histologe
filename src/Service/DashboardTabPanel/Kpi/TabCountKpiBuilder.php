<?php

namespace App\Service\DashboardTabPanel\Kpi;

use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
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

    public function setMesDossiers(?string $mesDossiersMessagesUsagers): self
    {
        $this->mesDossiersMessagesUsagers = $mesDossiersMessagesUsagers;

        return $this;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function withTabCountKpi(): static
    {
        /** @var User $user */
        $user = $this->security->getUser();
        if ($this->security->isGranted('ROLE_ADMIN_TERRITORY')) {
            $countNouveauxDossiers = $this->signalementRepository->countNouveauxDossiersKpi($this->territories);
            $countDossiersMessagesUsagers = $this->suiviRepository->countAllMessagesUsagers($this->territoryId, $this->mesDossiersMessagesUsagers);
        } else {
            $countNouveauxDossiers = $this->signalementRepository->countNouveauxDossiersKpi($this->territories, $user);
            $countDossiersMessagesUsagers = $this->suiviRepository->countAllMessagesUsagers($this->territoryId, $this->mesDossiersMessagesUsagers, $user);
        }
        $countDossiersAFermer = $this->signalementRepository->countAllDossiersAferme($user, $this->territoryId);

        $this->tabCountKpi = new TabCountKpi(
            countNouveauxDossiers: $countNouveauxDossiers->total(),
            countDossiersAFermer: $countDossiersAFermer->total(),
            countDossiersMessagesUsagers: $countDossiersMessagesUsagers->total(),
        );

        return $this;
    }

    public function build(): TabCountKpi
    {
        return $this->tabCountKpi;
    }
}
