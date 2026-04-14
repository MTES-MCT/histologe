<?php

namespace App\Service\DashboardTabPanel\Kpi;

use App\Entity\User;
use App\Repository\Query\Dashboard\DossiersQuery;
use App\Repository\Query\Dashboard\DossiersSansSuivisPartenaireQuery;
use App\Repository\Query\Dashboard\DossiersSuivisUsagerQuery;
use App\Repository\Query\Dashboard\DossiersUndeliverableEmailQuery;
use App\Repository\Query\Dashboard\NouveauxDossiersKpiQuery;
use App\Repository\Query\Dashboard\SignalementsSansAffectationAccepteeQuery;
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
    private ?string $mesDossiersActiviteRecente = null;
    private ?string $queryCommune = null;
    /** @var array<int, int> */
    private array $partners = [];

    public function __construct(
        private readonly NouveauxDossiersKpiQuery $nouveauxDossiersKpiQuery,
        private readonly Security $security,
        private readonly TabCountKpiCacheHelper $tabCountKpiCacheHelper,
        private readonly SignalementsSansAffectationAccepteeQuery $signalementsSansAffectationAccepteeQuery,
        private readonly DossiersSuivisUsagerQuery $dossiersSuivisUsagerQuery,
        private readonly DossiersQuery $dossiersQuery,
        private readonly DossiersSansSuivisPartenaireQuery $dossiersSansSuivisPartenaireQuery,
        private readonly DossiersUndeliverableEmailQuery $dossiersUndeliverableEmailQuery,
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

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function withTabCountKpi(): static
    {
        $params = new TabQueryParameters(
            territoireId: $this->territoryId,
            mesDossiersMessagesUsagers: $this->mesDossiersMessagesUsagers,
            mesDossiersAverifier: $this->mesDossiersAverifier,
            mesDossiersActiviteRecente: $this->mesDossiersActiviteRecente,
            queryCommune: $this->queryCommune,
            partners: $this->partners
        );
        /** @var User $user */
        $user = $this->security->getUser();
        if ($this->security->isGranted('ROLE_ADMIN_TERRITORY')) {
            $countNouveauxDossiers = $this->tabCountKpiCacheHelper->getOrSet(
                TabCountKpiCacheHelper::NOUVEAUX_DOSSIERS,
                $user,
                $params,
                fn () => $this->nouveauxDossiersKpiQuery->countNouveauxDossiersKpi($this->territories)
            );
        } else {
            $countNouveauxDossiers = $this->tabCountKpiCacheHelper->getOrSet(
                TabCountKpiCacheHelper::NOUVEAUX_DOSSIERS,
                $user,
                $params,
                fn () => $this->nouveauxDossiersKpiQuery->countNouveauxDossiersKpi($this->territories, $user)
            );
        }
        $countDossiersAFermer = $this->tabCountKpiCacheHelper->getOrSet(
            TabCountKpiCacheHelper::DOSSIERS_A_FERMER,
            $user,
            $params,
            fn () => $this->dossiersQuery->countAllDossiersAferme($user, $params)
        );
        $countDossiersMessagesUsagers = $this->tabCountKpiCacheHelper->getOrSet(
            TabCountKpiCacheHelper::DOSSIERS_MESSAGES_USAGERS,
            $user,
            $params,
            fn () => $this->dossiersSuivisUsagerQuery->countAllMessagesUsagers($user, $params)
        );
        $countDossiersAVerifier = $this->tabCountKpiCacheHelper->getOrSet(
            TabCountKpiCacheHelper::DOSSIERS_A_VERIFIER,
            $user,
            $params,
            fn () => $this->countAllDossiersAVerifier($user, $params)
        );

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

    private function countAllDossiersAVerifier(User $user, ?TabQueryParameters $params): CountDossiersAVerifier
    {
        return new CountDossiersAVerifier(
            countSignalementsSansAffectationAcceptee: $this->signalementsSansAffectationAccepteeQuery->countSignalements($user, $params),
            countSignalementsSansSuiviPartenaireDepuis60Jours: $this->dossiersSansSuivisPartenaireQuery->countSignalements($user, $params),
            countAdresseEmailAVerifier: $this->dossiersUndeliverableEmailQuery->count($user, $params)
        );
    }
}
