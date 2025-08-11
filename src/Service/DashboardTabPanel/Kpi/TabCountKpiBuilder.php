<?php

namespace App\Service\DashboardTabPanel\Kpi;

use App\Entity\User;
use App\Repository\SignalementRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\SecurityBundle\Security;

class TabCountKpiBuilder
{
    private ?TabCountKpi $tabCountKpi = null;

    /** @var array<int, mixed> */
    private array $territories = [];

    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly Security $security,
    ) {
    }

    /**
     * @param array<int, mixed> $territories
     */
    public function setTerritories(array $territories): self
    {
        $this->territories = $territories;

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
        } else {
            $countNouveauxDossiers = $this->signalementRepository->countNouveauxDossiersKpi($this->territories, $user);
        }

        $this->tabCountKpi = new TabCountKpi(
            countNouveauxDossiers: $countNouveauxDossiers->total(),
            countDossiersAFermer: 56
        );

        return $this;
    }

    public function build(): TabCountKpi
    {
        return $this->tabCountKpi;
    }
}
