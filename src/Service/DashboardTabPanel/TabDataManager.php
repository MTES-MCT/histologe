<?php

namespace App\Service\DashboardTabPanel;

use App\Dto\CountPartner;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\User;
use App\Repository\JobEventRepository;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
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
        private readonly SuiviRepository $suiviRepository,
        private readonly TerritoryRepository $territoryRepository,
        private readonly UserRepository $userRepository,
        private readonly PartnerRepository $partnerRepository,
        private readonly SignalementRepository $signalementRepository,
        private readonly TabCountKpiBuilder $tabCountKpiBuilder,
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
        ?string $queryCommune,
        ?array $partners,
    ): TabCountKpi {
        return $this->tabCountKpiBuilder
            ->setTerritories($territories, $territoryId)
            ->setMesDossiers($mesDossiersMessagesUsagers, $mesDossiersAverifier)
            ->setSearchAverifier($queryCommune, $partners)
            ->withTabCountKpi()
            ->build();
    }

    /**
     * @return TabDossier[]
     */
    public function getDernierActionDossiers(?TabQueryParameters $tabQueryParameters = null): array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $territory = null;
        if ($tabQueryParameters && $tabQueryParameters->territoireId) {
            $territory = $this->territoryRepository->find($tabQueryParameters->territoireId);
        }
        $signalements = $this->suiviRepository->findLastSignalementsWithUserSuivi($user, $territory, 10);
        $tabDossiers = [];
        if (empty($signalements)) {
            return $tabDossiers;
        }
        for ($i = 0; $i < \count($signalements); ++$i) {
            $signalement = $signalements[$i];
            $derniereAction = (SuiviCategory::MESSAGE_PARTNER === $signalement['suiviCategory'])
                ? ($signalement['suiviIsPublic'] ? 'Suivi visible par l\'usager' : 'Suivi interne')
                : $signalement['suiviCategory']->label();
            $tabDossiers[] = new TabDossier(
                nomDeclarant: $signalement['nomOccupant'],
                prenomDeclarant: $signalement['prenomOccupant'],
                reference: '#'.$signalement['reference'],
                adresse: $signalement['adresseOccupant'],
                statut: $signalement['statut']->label(),
                derniereAction: $derniereAction,
                derniereActionAt: $signalement['suiviCreatedAt'],
                actionDepuis: $signalement['hasNewerSuivi'] ? 'OUI' : 'NON',
                uuid: $signalement['uuid'],
            );
        }

        return $tabDossiers;
    }

    public function countUsersPendingToArchive(?TabQueryParameters $tabQueryParameters = null): int
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $territories = [];
        if ($tabQueryParameters && $tabQueryParameters->territoireId) {
            $territories[] = $this->territoryRepository->find($tabQueryParameters->territoireId);
        }

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

        return $this->userRepository->countAgentsPbEmail($user, $territories);
    }

    public function countPartenairesNonNotifiables(?TabQueryParameters $tabQueryParameters = null): int
    {
        $territories = [];
        if ($tabQueryParameters && $tabQueryParameters->territoireId) {
            $territories[] = $this->territoryRepository->find($tabQueryParameters->territoireId);
        }

        /** @var CountPartner $countPartnerDto */
        $countPartnerDto = $this->partnerRepository->countPartnerNonNotifiables($territories);

        return $countPartnerDto->getNonNotifiables();
    }

    public function countPartenairesInterfaces(?TabQueryParameters $tabQueryParameters = null): int
    {
        $territories = [];
        if ($tabQueryParameters && $tabQueryParameters->territoireId) {
            $territories[] = $this->territoryRepository->find($tabQueryParameters->territoireId);
        }

        return $this->partnerRepository->countPartnerInterfaces($territories);
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
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getNouveauxDossiersWithCount(
        ?SignalementStatus $signalementStatus = null,
        ?AffectationStatus $affectationStatus = null,
        ?TabQueryParameters $tabQueryParameters = null,
    ): TabDossierResult {
        $dossiers = $this->signalementRepository->findNewDossiersFrom(
            signalementStatus: $signalementStatus,
            affectationStatus: $affectationStatus,
            tabQueryParameters: $tabQueryParameters
        );

        $count = $this->signalementRepository->countNewDossiersFrom(
            signalementStatus: $signalementStatus,
            affectationStatus: $affectationStatus,
            tabQueryParameters: $tabQueryParameters
        );

        return new TabDossierResult($dossiers, $count);
    }

    public function getDossiersNoAgentWithCount(
        ?TabQueryParameters $tabQueryParameters = null,
        ?AffectationStatus $affectationStatus = null,
    ): TabDossierResult {
        $dossiers = $this->signalementRepository->findDossiersNoAgentFrom(
            affectationStatus: $affectationStatus,
            tabQueryParameters: $tabQueryParameters
        );

        $count = $this->signalementRepository->countDossiersNoAgentFrom(
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

        $dossiers = $this->signalementRepository->findNewDossiersFrom(
            signalementStatus: $signalementStatus,
            tabQueryParameters: $tabQueryParameters
        );

        $count = $this->signalementRepository->countNewDossiersFrom(
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
        $dossiers = $this->signalementRepository->findDossiersFermePartenaireTous(
            tabQueryParameters: $tabQueryParameters
        );

        $count = $this->signalementRepository->countDossiersFermePartenaireTous(
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
        $dossiers = $this->signalementRepository->findDossiersDemandesFermetureByUsager(
            tabQueryParameters: $tabQueryParameters
        );

        $count = $this->signalementRepository->countDossiersDemandesFermetureByUsager(
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
        $dossiers = $this->signalementRepository->findSignalementsAvecRelancesSansReponse(
            tabQueryParameters: $tabQueryParameters
        );

        $count = $this->signalementRepository->countSignalementsAvecRelancesSansReponse(tabQueryParameters: $tabQueryParameters);

        return new TabDossierResult($dossiers, $count);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getMessagesUsagersNouveauxMessages(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User $user */
        $user = $this->security->getUser();

        // Regroupe les signalements dont le dernier suivi est un message usager spontané, c'est-à-dire qui ne fait pas immédiatement suite à une relance auto.
        $suivis = $this->suiviRepository->findSuivisUsagersWithoutAskFeedbackBefore(user: $user, params: $tabQueryParameters);
        $tabDossiers = [];
        for ($i = 0; $i < \count($suivis); ++$i) {
            $suivi = $suivis[$i];
            $tabDossiers[] = new TabDossier(
                nomDeclarant: $suivi['nomOccupant'],
                prenomDeclarant: $suivi['prenomOccupant'],
                reference: '#'.$suivi['reference'],
                adresse: $suivi['adresse'],
                messageAt: new \DateTimeImmutable($suivi['messageAt']),
                messageSuiviByNom: $suivi['messageSuiviByNom'],
                messageSuiviByPrenom: $suivi['messageSuiviByPrenom'],
                messageByProfileDeclarant: $suivi['messageByProfileDeclarant'],
                uuid: $suivi['uuid'],
            );
        }

        $count = $this->suiviRepository->countSuivisUsagersWithoutAskFeedbackBefore(user: $user, params: $tabQueryParameters);

        return new TabDossierResult($tabDossiers, $count);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getMessagesUsagersMessageApresFermeture(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User $user */
        $user = $this->security->getUser();

        // Signalements fermés dont l'usager a fait un dernier suivi après fermeture
        $suivis = $this->suiviRepository->findSuivisPostCloture(user: $user, params: $tabQueryParameters);
        $tabDossiers = [];
        for ($i = 0; $i < \count($suivis); ++$i) {
            $suivi = $suivis[$i];
            $tabDossiers[] = new TabDossier(
                nomDeclarant: $suivi['nomOccupant'],
                prenomDeclarant: $suivi['prenomOccupant'],
                reference: '#'.$suivi['reference'],
                adresse: $suivi['adresse'],
                clotureAt: $suivi['clotureAt'],
                messageAt: new \DateTimeImmutable($suivi['messageAt']),
                messageSuiviByNom: $suivi['messageSuiviByNom'],
                messageSuiviByPrenom: $suivi['messageSuiviByPrenom'],
                messageByProfileDeclarant: $suivi['messageByProfileDeclarant'],
                uuid: $suivi['uuid'],
            );
        }

        $count = $this->suiviRepository->countSuivisPostCloture(user: $user, params: $tabQueryParameters);

        return new TabDossierResult($tabDossiers, $count);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getMessagesUsagersMessagesSansReponse(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User $user */
        $user = $this->security->getUser();

        // signalements ayant un message usager ou demande de poursuite de procédure sans suivis partenaires public depuis la demande de feedback
        $suivis = $this->suiviRepository->findSuivisUsagerOrPoursuiteWithAskFeedbackBefore(user: $user, params: $tabQueryParameters);
        $tabDossiers = [];
        for ($i = 0; $i < \count($suivis); ++$i) {
            $suivi = $suivis[$i];
            $tabDossiers[] = new TabDossier(
                nomDeclarant: $suivi['nomOccupant'],
                prenomDeclarant: $suivi['prenomOccupant'],
                reference: '#'.$suivi['reference'],
                adresse: $suivi['adresse'],
                messageAt: new \DateTimeImmutable($suivi['messageAt']),
                messageSuiviByNom: $suivi['messageSuiviByNom'],
                messageSuiviByPrenom: $suivi['messageSuiviByPrenom'],
                messageByProfileDeclarant: $suivi['messageByProfileDeclarant'],
                messageDaysAgo: $suivi['messageDaysAgo'],
                uuid: $suivi['uuid'],
            );
        }

        $count = $this->suiviRepository->countSuivisUsagerOrPoursuiteWithAskFeedbackBefore(user: $user, params: $tabQueryParameters);

        return new TabDossierResult($tabDossiers, $count);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getDossiersAVerifierSansActivitePartenaires(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $signalements = $this->signalementRepository->findSignalementsSansSuiviPartenaireDepuis60Jours(user: $user, params: $tabQueryParameters);
        $tabDossiers = [];
        for ($i = 0; $i < \count($signalements); ++$i) {
            $signalement = $signalements[$i];
            $tabDossiers[] = new TabDossier(
                nomDeclarant: $signalement['nomOccupant'],
                prenomDeclarant: $signalement['prenomOccupant'],
                reference: '#'.$signalement['reference'],
                adresse: $signalement['adresse'],
                derniereActionAt: new \DateTimeImmutable($signalement['dernierSuiviAt']),
                derniereActionPartenaireDaysAgo: $signalement['nbJoursDepuisDernierSuivi'],
                derniereActionTypeSuivi: SuiviCategory::from($signalement['suiviCategory'])->label(),
                derniereActionPartenaireNom: $signalement['derniereActionPartenaireNom'] ?? 'N/A',
                derniereActionPartenaireNomAgent: $signalement['derniereActionPartenaireNomAgent'] ?? 'N/A',
                derniereActionPartenairePrenomAgent: $signalement['derniereActionPartenairePrenomAgent'] ?? 'N/A',
                uuid: $signalement['uuid'],
            );
        }

        $count = $this->signalementRepository->countSignalementsSansSuiviPartenaireDepuis60Jours(user: $user, params: $tabQueryParameters);

        return new TabDossierResult($tabDossiers, $count);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getDossiersAVerifierAdresseEmailAverifier(?TabQueryParameters $tabQueryParameters = null): TabDossierResult
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $signalements = $this->signalementRepository->findActiveSignalementsWithInvalidEmails(user: $user, params: $tabQueryParameters);
        $tabDossiers = [];
        for ($i = 0; $i < \count($signalements); ++$i) {
            $signalement = $signalements[$i];
            $tabDossiers[] = new TabDossier(
                profilNonDeliverable: $signalement['profilNonDeliverable'],
                reference: '#'.$signalement['reference'],
                nomDeclarant: $signalement['nomOccupant'],
                prenomDeclarant: $signalement['prenomOccupant'],
                adresse: $signalement['adresse'],
                depotAt: $signalement['createdAt'],
                derniereActionAt: $signalement['dernierSuiviAt'],
                derniereActionPartenaireNom: $signalement['derniereActionPartenaireNom'] ?? 'N/A',
                uuid: $signalement['uuid'],
            );
        }

        $count = $this->signalementRepository->countNonDeliverableSignalements(user: $user, params: $tabQueryParameters);

        return new TabDossierResult($tabDossiers, $count);
    }
}
