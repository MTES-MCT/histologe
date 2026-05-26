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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(TabCountKpiCalculatorInterface::class)]
class TabCountKpiCalculator implements TabCountKpiCalculatorInterface
{
    public function __construct(
        private readonly NouveauxDossiersKpiQuery $nouveauxDossiersKpiQuery,
        private readonly SignalementsSansAffectationAccepteeQuery $signalementsSansAffectationAccepteeQuery,
        private readonly DossiersSuivisUsagerQuery $dossiersSuivisUsagerQuery,
        private readonly DossiersQuery $dossiersQuery,
        private readonly DossiersSansSuivisPartenaireQuery $dossiersSansSuivisPartenaireQuery,
        private readonly DossiersUndeliverableEmailQuery $dossiersUndeliverableEmailQuery,
        private readonly Security $security,
    ) {
    }

    /**
     * @param array<int, mixed> $territories
     */
    public function countNouveauxDossiers(array $territories, User $user, TabQueryParameters $params): CountNouveauxDossiers
    {
        if ($this->security->isGranted('ROLE_ADMIN_TERRITORY')) {
            return $this->nouveauxDossiersKpiQuery->countNouveauxDossiersKpi($territories);
        }

        return $this->nouveauxDossiersKpiQuery->countNouveauxDossiersKpi($territories, $user);
    }

    public function countDossiersAFermer(User $user, TabQueryParameters $params): CountAfermer
    {
        return $this->dossiersQuery->countAllDossiersAferme($user, $params);
    }

    public function countDossiersMessagesUsagers(User $user, TabQueryParameters $params): CountDossiersMessagesUsagers
    {
        return $this->dossiersSuivisUsagerQuery->countAllMessagesUsagers($user, $params);
    }

    public function countDossiersAVerifier(User $user, TabQueryParameters $params): CountDossiersAVerifier
    {
        return new CountDossiersAVerifier(
            countSignalementsSansSuiviPartenaireDepuis60Jours: $this->dossiersSansSuivisPartenaireQuery->countSignalements($user, $params),
            countSignalementsSansAffectationAcceptee: $this->signalementsSansAffectationAccepteeQuery->countSignalements($user, $params),
            countAdresseEmailAVerifier: $this->dossiersUndeliverableEmailQuery->count($user, $params)
        );
    }
}
