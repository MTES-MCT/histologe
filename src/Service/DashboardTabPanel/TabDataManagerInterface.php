<?php

namespace App\Service\DashboardTabPanel;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Service\DashboardTabPanel\Kpi\TabCountKpi;

interface TabDataManagerInterface
{
    /**
     * @param array<int, mixed> $territories
     * @param array<int, int>   $partners
     */
    public function countDataKpi(array $territories, ?int $territoryId, ?string $mesDossiersMessagesUsagers, ?string $mesDossiersAverifier, ?string $queryCommune, ?array $partners): TabCountKpi;

    /**
     * @return TabDossier[]
     */
    public function getDernierActionDossiers(?TabQueryParameters $tabQueryParameters = null): array;

    public function countUsersPendingToArchive(?TabQueryParameters $tabQueryParameters = null): int;

    public function countPartenairesNonNotifiables(?TabQueryParameters $tabQueryParameters = null): int;

    public function countPartenairesInterfaces(?TabQueryParameters $tabQueryParameters = null): int;

    /**
     * @return array<string, bool|\DateTimeImmutable|null>
     */
    public function getInterconnexions(?TabQueryParameters $tabQueryParameters = null): array;

    public function getNouveauxDossiersWithCount(?SignalementStatus $signalementStatus = null, ?AffectationStatus $affectationStatus = null, ?TabQueryParameters $tabQueryParameters = null): TabDossierResult;

    public function getDossierNonAffectationWithCount(SignalementStatus $signalementStatus, ?TabQueryParameters $tabQueryParameters = null): TabDossierResult;

    public function getMessagesUsagersNouveauxMessages(?TabQueryParameters $tabQueryParameters = null): TabDossierResult;

    public function getMessagesUsagersMessageApresFermeture(?TabQueryParameters $tabQueryParameters = null): TabDossierResult;

    public function getMessagesUsagersMessagesSansReponse(?TabQueryParameters $tabQueryParameters = null): TabDossierResult;

    public function getDossiersAVerifierSansActivitePartenaires(?TabQueryParameters $tabQueryParameters = null): TabDossierResult;

    public function getDossiersDemandesFermetureByUsager(?TabQueryParameters $tabQueryParameters = null): TabDossierResult;

    public function getDossiersRelanceSansReponse(?TabQueryParameters $tabQueryParameters = null): TabDossierResult;

    public function getDossiersFermePartenaireTous(?TabQueryParameters $tabQueryParameters = null): TabDossierResult;
}
