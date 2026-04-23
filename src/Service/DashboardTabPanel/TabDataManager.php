<?php

namespace App\Service\DashboardTabPanel;

use App\Dto\CountPartner;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\JobEventRepository;
use App\Repository\Query\Dashboard\DossiersActiviteRecenteQuery;
use App\Repository\Query\Dashboard\DossiersAvecRelanceSansReponseQuery;
use App\Repository\Query\Dashboard\DossiersQuery;
use App\Repository\Query\Dashboard\DossiersSansSuivisPartenaireQuery;
use App\Repository\Query\Dashboard\DossiersSuivisUsagerQuery;
use App\Repository\Query\Dashboard\DossiersUndeliverableEmailQuery;
use App\Repository\Query\Dashboard\KpiQuery;
use App\Repository\Query\Dashboard\SignalementsSansAffectationAccepteeQuery;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\DashboardTabPanel\Kpi\TabCountKpi;
use App\Service\DashboardTabPanel\Kpi\TabCountKpiBuilder;
use App\Service\ListFilters\SearchInterconnexion;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\SecurityBundle\Security;

class TabDataManager
{
    private const int DAY_PERIOD = 365;

    public function __construct(
        private readonly Security $security,
        private readonly JobEventRepository $jobEventRepository,
        private readonly TerritoryRepository $territoryRepository,
        private readonly UserRepository $userRepository,
        private readonly TabCountKpiBuilder $tabCountKpiBuilder,
        private readonly SignalementsSansAffectationAccepteeQuery $signalementsSansAffectationAccepteeQuery,
        private readonly DossiersQuery $dossiersQuery,
        private readonly DossiersActiviteRecenteQuery $dossiersActiviteRecenteQuery,
        private readonly DossiersAvecRelanceSansReponseQuery $dossiersAvecRelanceSansReponseQuery,
        private readonly DossiersSuivisUsagerQuery $dossiersSuivisUsagerQuery,
        private readonly DossiersSansSuivisPartenaireQuery $dossiersSansSuivisPartenaireQuery,
        private readonly DossiersUndeliverableEmailQuery $dossiersUndeliverableEmailQuery,
        private readonly KpiQuery $kpiQuery,
    ) {
    }

    /**
     * @param array<int, mixed> $territories
     * @param array<int, int>   $partners
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countDataKpi(
        array $territories,
        ?int $territoryId,
        ?string $mesDossiersMessagesUsagers,
        ?string $mesDossiersAverifier,
        ?string $mesDossiersActiviteRecente,
        ?string $queryCommune,
        ?array $partners,
    ): TabCountKpi {
        return $this->tabCountKpiBuilder
            ->setTerritories($territories, $territoryId)
            ->setMesDossiers($mesDossiersMessagesUsagers, $mesDossiersAverifier, $mesDossiersActiviteRecente)
            ->setSearchAverifier($queryCommune, $partners)
            ->withTabCountKpi()
            ->build();
    }

    /**
     * @return array{
     *     data: TabDossier[],
     *     total: int,
     *     page: int
     * }
     */
    public function getDernierActionDossiers(?TabQueryParameters $tabQueryParameters = null): array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $territory = null;
        if ($tabQueryParameters && $tabQueryParameters->territoireId) {
            $territory = $this->territoryRepository->find($tabQueryParameters->territoireId);
        }

        $count = $this->dossiersActiviteRecenteQuery
                ->countLastSignalementsWithUserSuivi($user, $territory);

        $page = $tabQueryParameters?->page ?? 1;
        $limit = TabDossier::MAX_ITEMS_DERNIERES_ACTIONS;
        $totalPages = max(1, (int) ceil($count / $limit));
        $page = max(1, min($page, $totalPages));

        $paginator = $this->dossiersActiviteRecenteQuery
            ->findPaginatedLastSignalementsWithUserSuivi($user, $territory, $page, $limit);
        $tabDossiers = [];
        foreach ($paginator as $signalement) {
            $derniereAction = (SuiviCategory::MESSAGE_PARTNER === $signalement['suiviCategory'])
                ? ($signalement['suiviIsPublic'] ? 'Suivi visible par l\'usager' : 'Suivi interne')// TODO : à changer ?
                : $signalement['suiviCategory']->label();
            $tabDossiers[] = new TabDossier(
                uuid: $signalement['uuid'],
                nomOccupant: $signalement['nomOccupant'],
                prenomOccupant: $signalement['prenomOccupant'],
                reference: '#'.$signalement['reference'],
                adresse: $signalement['adresseOccupant'],
                statut: $signalement['statut']->label(),
                derniereAction: $derniereAction,
                derniereActionAt: $signalement['suiviCreatedAt'],
                actionDepuis: $signalement['hasNewerSuivi'] ? 'OUI' : 'NON',
            );
        }

        return [
            'data' => $tabDossiers,
            'total' => $count,
            'page' => $page,
        ];
    }

    public function countInjonctions(?TabQueryParameters $tabQueryParameters = null): int
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $injonctions = $this->kpiQuery->countInjonctions($user, $tabQueryParameters);

        return $injonctions;
    }

    public function countUsersPendingToArchive(?TabQueryParameters $tabQueryParameters = null): int
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $territories = [];
        if ($tabQueryParameters && $tabQueryParameters->territoireId) {
            $territories[] = $this->territoryRepository->find($tabQueryParameters->territoireId);
        }

        /** @var array<int, User> $users */
        $users = $this->userRepository->findUsersPendingToArchive($user, $territories);

        return \count($users);
    }

    public function countUsersPbEmail(?TabQueryParameters $tabQueryParameters = null): int
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $territories = [];
        if ($tabQueryParameters && $tabQueryParameters->territoireId) {
            $territories[] = $this->territoryRepository->find($tabQueryParameters->territoireId);
        }

        return $this->kpiQuery->countAgentsPbEmail($user, $territories);
    }

    public function countPartenairesNonNotifiables(?TabQueryParameters $tabQueryParameters = null): int
    {
        $territories = [];
        if ($tabQueryParameters && $tabQueryParameters->territoireId) {
            $territories[] = $this->territoryRepository->find($tabQueryParameters->territoireId);
        }

        /** @var CountPartner $countPartnerDto */
        $countPartnerDto = $this->kpiQuery->countPartnerNonNotifiables($territories);

        return $countPartnerDto->getNonNotifiables();
    }

    public function countPartenairesInterfaces(?TabQueryParameters $tabQueryParameters = null): int
    {
        $territories = [];
        if ($tabQueryParameters && $tabQueryParameters->territoireId) {
            $territories[] = $this->territoryRepository->find($tabQueryParameters->territoireId);
        }

        return $this->kpiQuery->countPartnerInterfaces($territories);
    }

    /**
     * @return array<string, bool|\DateTimeImmutable|null>
     *
     * @throws \DateMalformedStringException
     */
    public function getInterconnexions(?TabQueryParameters $tabQueryParameters = null): array
    {
        $searchInterconnexion = new SearchInterconnexion();
        $searchInterconnexion->setOrderType('j.createdAt-DESC');
        $territory = null;
        $lastErrorSynchro = [];
        if ($tabQueryParameters && $tabQueryParameters->territoireId) {
            $territory = $this->territoryRepository->find($tabQueryParameters->territoireId);
        }
        $searchInterconnexion->setTerritory($territory);

        $lastConnection = $this->jobEventRepository->findLastJobEventByTerritory(
            self::DAY_PERIOD,
            $searchInterconnexion,
            1,
            0
        );

        $lastSynchro = null;
        if (!empty($lastConnection)) {
            $lastSynchro = $lastConnection[0];
        }

        $searchInterconnexion->setStatus('failed');

        $errorConnectionLastDay = $this->jobEventRepository->findLastJobEventByTerritory(
            1,
            $searchInterconnexion,
            1,
            0
        );

        $hasErrorLastDay = false;
        if (!empty($errorConnectionLastDay)) {
            $hasErrorLastDay = true;
            $lastErrorSynchro = $errorConnectionLastDay[0];
        }

        return [
            'hasErrorsLastDay' => $hasErrorLastDay,
            'firstErrorLastDayAt' => $hasErrorLastDay ? $lastErrorSynchro['createdAt'] : null,
            'LastSyncAt' => $lastSynchro ? $lastSynchro['createdAt'] : null,
        ];
    }

    /**
     * @param Territory[] $territoires
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getNouveauxDossiersWithCount(
        ?SignalementStatus $signalementStatus = null,
        ?AffectationStatus $affectationStatus = null,
        ?TabQueryParameters $tabQueryParameters = null,
        array $territoires = [],
    ): TabDossierResult {
        /** @var User $user */
        $user = $this->security->getUser();
        if (!$user->isSuperAdmin() && !$user->isTerritoryAdmin()) {
            $tabQueryParameters = $this->initTabQueryParametersPartenairesId(
                $tabQueryParameters,
                $territoires,
            );
        }

        $dossiers = $this->dossiersQuery->findNewDossiersFrom(
            signalementStatus: $signalementStatus,
            affectationStatus: $affectationStatus,
            tabQueryParameters: $tabQueryParameters
        );

        $count = $this->dossiersQuery->countNewDossiersFrom(
            signalementStatus: $signalementStatus,
            affectationStatus: $affectationStatus,
            tabQueryParameters: $tabQueryParameters
        );

        return new TabDossierResult($dossiers, $count);
    }

    /**
     * @param Territory[] $territoires
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getDossiersNoAgentWithCount(
        ?TabQueryParameters $tabQueryParameters = null,
        ?AffectationStatus $affectationStatus = null,
        array $territoires = [],
    ): TabDossierResult {
        $tabQueryParameters = $this->initTabQueryParametersPartenairesId(
            $tabQueryParameters,
            $territoires,
        );

        $dossiers = $this->dossiersQuery->findDossiersNoAgentFrom(
            affectationStatus: $affectationStatus,
            tabQueryParameters: $tabQueryParameters
        );

        $count = $this->dossiersQuery->countDossiersNoAgentFrom(
            affectationStatus: $affectationStatus,
            tabQueryParameters: $tabQueryParameters
        );

        return new TabDossierResult($dossiers, $count);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getDossierNonAffectationWithCount(
        SignalementStatus $signalementStatus,
        ?TabQueryParameters $tabQueryParameters = null,
    ): TabDossierResult {
        $tabQueryParameters->partenairesId = ['AUCUN'];

        $dossiers = $this->dossiersQuery->findNewDossiersFrom(
            signalementStatus: $signalementStatus,
            tabQueryParameters: $tabQueryParameters
        );

        $count = $this->dossiersQuery->countNewDossiersFrom(
            signalementStatus: $signalementStatus,
            tabQueryParameters: $tabQueryParameters
        );

        return new TabDossierResult($dossiers, $count);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function getDossiersFermePartenaireTous(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        $dossiers = $this->dossiersQuery->findDossiersFermePartenaireTous(
            tabQueryParameters: $tabQueryParameters
        );

        $count = $this->dossiersQuery->countDossiersFermePartenaireTous(
            tabQueryParameters: $tabQueryParameters
        );

        return new TabDossierResult($dossiers, $count);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function getDossiersFermePartenaireCommune(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        $dossiers = $this->dossiersQuery->findDossiersFermePartenaireCommune(
            tabQueryParameters: $tabQueryParameters
        );

        $count = $this->dossiersQuery->countDossiersFermePartenaireCommune(
            tabQueryParameters: $tabQueryParameters
        );

        return new TabDossierResult($dossiers, $count);
    }

    /**
     * @throws \DateMalformedStringException
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getDossiersDemandesFermetureByUsager(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        $dossiers = $this->dossiersQuery->findDossiersDemandesFermetureByUsager(
            tabQueryParameters: $tabQueryParameters
        );

        $count = $this->dossiersQuery->countDossiersDemandesFermetureByUsager(
            tabQueryParameters: $tabQueryParameters
        );

        return new TabDossierResult($dossiers, $count);
    }

    /**
     * @throws \DateMalformedStringException
     * @throws Exception
     */
    public function getDossiersRelanceSansReponse(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        $dossiers = $this->dossiersAvecRelanceSansReponseQuery->findSignalements(
            tabQueryParameters: $tabQueryParameters
        );

        $count = $this->dossiersAvecRelanceSansReponseQuery->countSignalements(tabQueryParameters: $tabQueryParameters);

        return new TabDossierResult($dossiers, $count);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @throws \DateMalformedStringException
     */
    public function getMessagesUsagersNouveauxMessages(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User $user */
        $user = $this->security->getUser();

        // Regroupe les signalements dont le dernier suivi est un message usager spontané, c'est-à-dire qui ne fait pas immédiatement suite à une relance auto.
        $suivis = $this->dossiersSuivisUsagerQuery->findSuivisUsagersWithoutAskFeedbackBefore(user: $user, params: $tabQueryParameters);
        $tabDossiers = [];
        for ($i = 0; $i < \count($suivis); ++$i) {
            $suivi = $suivis[$i];
            $tabDossiers[] = new TabDossier(
                uuid: $suivi['uuid'],
                nomOccupant: $suivi['nomOccupant'],
                prenomOccupant: $suivi['prenomOccupant'],
                reference: '#'.$suivi['reference'],
                adresse: $suivi['adresse'],
                messageAt: new \DateTimeImmutable($suivi['messageAt']),
                messageSuiviByNom: $suivi['messageSuiviByNom'],
                messageSuiviByPrenom: $suivi['messageSuiviByPrenom'],
                messageByProfileDeclarant: $suivi['messageByProfileDeclarant'],
            );
        }

        $count = $this->dossiersSuivisUsagerQuery->countSuivisUsagersWithoutAskFeedbackBefore(user: $user, params: $tabQueryParameters);

        return new TabDossierResult($tabDossiers, $count);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function getMessagesUsagersMessageApresFermeture(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User $user */
        $user = $this->security->getUser();

        // Signalements fermés dont l'usager a fait un dernier suivi après fermeture
        $suivis = $this->dossiersSuivisUsagerQuery->findSuivisPostCloture(user: $user, params: $tabQueryParameters);
        $tabDossiers = [];
        for ($i = 0; $i < \count($suivis); ++$i) {
            $suivi = $suivis[$i];
            $tabDossiers[] = new TabDossier(
                uuid: $suivi['uuid'],
                nomOccupant: $suivi['nomOccupant'],
                prenomOccupant: $suivi['prenomOccupant'],
                reference: '#'.$suivi['reference'],
                adresse: $suivi['adresse'],
                clotureAt: $suivi['clotureAt'],
                messageAt: new \DateTimeImmutable($suivi['messageAt']),
                messageSuiviByNom: $suivi['messageSuiviByNom'],
                messageSuiviByPrenom: $suivi['messageSuiviByPrenom'],
                messageByProfileDeclarant: $suivi['messageByProfileDeclarant'],
            );
        }

        $count = $this->dossiersSuivisUsagerQuery->countSuivisPostCloture(user: $user, params: $tabQueryParameters);

        return new TabDossierResult($tabDossiers, $count);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function getMessagesUsagersMessagesSansReponse(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User $user */
        $user = $this->security->getUser();

        // signalements ayant un message usager ou demande de poursuite de procédure sans suivis partenaires public depuis la demande de feedback
        $suivis = $this->dossiersSuivisUsagerQuery->findSuivisUsagerOrPoursuiteWithAskFeedbackBefore(user: $user, params: $tabQueryParameters);
        $tabDossiers = [];
        for ($i = 0; $i < \count($suivis); ++$i) {
            $suivi = $suivis[$i];
            $tabDossiers[] = new TabDossier(
                uuid: $suivi['uuid'],
                nomOccupant: $suivi['nomOccupant'],
                prenomOccupant: $suivi['prenomOccupant'],
                reference: '#'.$suivi['reference'],
                adresse: $suivi['adresse'],
                messageAt: new \DateTimeImmutable($suivi['messageAt']),
                messageDaysAgo: $suivi['messageDaysAgo'],
                messageSuiviByNom: $suivi['messageSuiviByNom'],
                messageSuiviByPrenom: $suivi['messageSuiviByPrenom'],
                messageByProfileDeclarant: $suivi['messageByProfileDeclarant'],
            );
        }

        $count = $this->dossiersSuivisUsagerQuery->countSuivisUsagerOrPoursuiteWithAskFeedbackBefore(user: $user, params: $tabQueryParameters);

        return new TabDossierResult($tabDossiers, $count);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function getDossiersAVerifierSansActivitePartenaires(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $signalements = $this->dossiersSansSuivisPartenaireQuery->findSignalements(user: $user, params: $tabQueryParameters);
        $tabDossiers = [];
        for ($i = 0; $i < \count($signalements); ++$i) {
            $signalement = $signalements[$i];
            $tabDossiers[] = new TabDossier(
                uuid: $signalement['uuid'],
                nomOccupant: $signalement['nomOccupant'],
                prenomOccupant: $signalement['prenomOccupant'],
                reference: '#'.$signalement['reference'],
                adresse: $signalement['adresse'],
                derniereActionAt: new \DateTimeImmutable($signalement['dernierSuiviAt']),
                derniereActionTypeSuivi: SuiviCategory::from($signalement['suiviCategory'])->label(),
                derniereActionPartenaireDaysAgo: $signalement['nbJoursDepuisDernierSuivi'],
                derniereActionPartenaireNom: $signalement['derniereActionPartenaireNom'] ?? 'N/A',
                derniereActionPartenaireNomAgent: $signalement['derniereActionPartenaireNomAgent'] ?? 'N/A',
                derniereActionPartenairePrenomAgent: $signalement['derniereActionPartenairePrenomAgent'] ?? 'N/A',
            );
        }

        $count = $this->dossiersSansSuivisPartenaireQuery->countSignalements(user: $user, params: $tabQueryParameters);

        return new TabDossierResult($tabDossiers, $count);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function getDossiersAVerifierSansAffectationAcceptee(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $signalements = $this->signalementsSansAffectationAccepteeQuery->findSignalements(user: $user, params: $tabQueryParameters);
        $tabDossiers = [];
        for ($i = 0; $i < \count($signalements); ++$i) {
            $signalement = $signalements[$i];
            $tabDossiers[] = new TabDossier(
                uuid: $signalement['uuid'],
                nomOccupant: $signalement['nomOccupant'],
                prenomOccupant: $signalement['prenomOccupant'],
                reference: '#'.$signalement['reference'],
                adresse: $signalement['adresse'],
                parc: $signalement['parc'],
                derniereAffectationAt: new \DateTimeImmutable($signalement['lastAffectationAt']),
                nbAffectations: $signalement['nbAffectations'],
            );
        }
        $count = $this->signalementsSansAffectationAccepteeQuery->countSignalements(user: $user, params: $tabQueryParameters);

        return new TabDossierResult($tabDossiers, $count);
    }

    public function getDossiersAVerifierAdresseEmailAverifier(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $signalements = $this->dossiersUndeliverableEmailQuery->findSignalements(user: $user, params: $tabQueryParameters);
        $tabDossiers = [];
        for ($i = 0; $i < \count($signalements); ++$i) {
            $signalement = $signalements[$i];
            $tabDossiers[] = new TabDossier(
                uuid: $signalement['uuid'],
                nomOccupant: $signalement['nomOccupant'],
                prenomOccupant: $signalement['prenomOccupant'],
                reference: '#'.$signalement['reference'],
                adresse: $signalement['adresse'],
                depotAt: $signalement['createdAt'],
                derniereActionAt: $signalement['dernierSuiviAt'],
                derniereActionPartenaireNom: $signalement['derniereActionPartenaireNom'] ?? 'N/A',
                profilNonDeliverable: $signalement['profilNonDeliverable'],
            );
        }

        $count = $this->dossiersUndeliverableEmailQuery->count(user: $user, params: $tabQueryParameters);

        return new TabDossierResult($tabDossiers, $count);
    }

    /**
     * @param Territory[] $territoires
     */
    private function initTabQueryParametersPartenairesId(
        ?TabQueryParameters $tabQueryParameters = null,
        array $territoires = [],
    ): ?TabQueryParameters {
        if (null === $tabQueryParameters) {
            return null;
        }

        /** @var User $user */
        $user = $this->security->getUser();

        if (null === $tabQueryParameters->territoireId) {
            $tabQueryParameters->partenairesId = $user->getPartners()
                ->map(static fn ($partner) => $partner->getId())
                ->toArray();
        } else {
            $territoire = $territoires[$tabQueryParameters->territoireId];
            $partner = $user->getPartnerInTerritory($territoire);
            $tabQueryParameters->partenairesId = [$partner->getId()];
        }

        return $tabQueryParameters;
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getDossiersActiviteRecente(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $signalements = $this->dossiersActiviteRecenteQuery->findLastSignalementsWithOtherUserSuivi($user, $tabQueryParameters, 11);
        $tabDossiers = [];
        if (empty($signalements)) {
            return new TabDossierResult($tabDossiers, 0);
        }
        $displayedSignalements = \array_slice($signalements, 0, 10);

        foreach ($displayedSignalements as $signalement) {
            $derniereAction = (SuiviCategory::MESSAGE_PARTNER === $signalement['suiviCategory'])
                ? ($signalement['suiviIsPublic'] ? 'Suivi visible par l\'usager' : 'Suivi interne')// TODO : à changer ?
                : $signalement['suiviCategory']->label();
            $tabDossiers[] = new TabDossier(
                uuid: $signalement['uuid'],
                nomOccupant: $signalement['nomOccupant'],
                prenomOccupant: $signalement['prenomOccupant'],
                reference: '#'.$signalement['reference'],
                adresse: $signalement['adresseOccupant'],
                statut: $signalement['statut']->label(),
                derniereAction: $derniereAction,
                derniereActionAt: $signalement['suiviCreatedAt'],
                derniereActionPartenaireNom: $signalement['derniereActionPartenaireNom'] ?? 'N/A',
                derniereActionPartenaireNomAgent: $signalement['derniereActionPartenaireNomAgent'] ?? 'N/A',
                derniereActionPartenairePrenomAgent: $signalement['derniereActionPartenairePrenomAgent'] ?? 'N/A',
            );
        }

        $count = \count($signalements);

        return new TabDossierResult($tabDossiers, $count);
    }
}
