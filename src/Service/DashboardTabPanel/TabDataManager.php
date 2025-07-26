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
                derniereActionAt: $signalement['suiviCreatedAt']->format('d/m/Y'),
                actionDepuis: $signalement['hasNewerSuivi'] ? 'OUI' : 'NON',
                lien: '/bo/signalements/'.$signalement['uuid'],
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
     * @param array<int, mixed> $territories
     *
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countDataKpi(array $territories): TabCountKpi
    {
        return $this->tabCountKpiBuilder
            ->setTerritories($territories)
            ->withTabCountKpi()
            ->build();
    }

    /**
     * @return TabDossier[]
     */
    public function getMessagesUsagersNouveauxMessages(?TabQueryParameters $tabQueryParameters = null): array
    {
        return [
            new TabDossier(
                nomDeclarant: 'Abdallah',
                prenomDeclarant: 'Karim',
                reference: '#2022-150',
                adresse: '8 rue du Péronnet, 63390 Vernaison',
                messageAt: '06/06/2025 à 07:13',
                messageSuiviByNom: 'Abdallah',
                messageSuiviByPrenom: 'Karim',
                messageByProfileDeclarant: 'OCCUPANT'
            ),
            new TabDossier(
                nomDeclarant: 'Abdallah',
                prenomDeclarant: 'Karim',
                reference: '#2022-151',
                adresse: '9 rue du Péronnet, 63390 Vernaison',
                messageAt: '06/06/2025 à 07:13',
                messageSuiviByNom: 'Abdallah',
                messageSuiviByPrenom: 'Karim',
                messageByProfileDeclarant: 'TIERS DECLARANT'
            ),
        ];
    }

    /**
     * @return TabDossier[]
     */
    public function getMessagesUsagersMessageApresFermeture(?TabQueryParameters $tabQueryParameters = null): array
    {
        return [
            new TabDossier(
                nomDeclarant: 'Abdallah',
                prenomDeclarant: 'Karim',
                reference: '#2022-150',
                adresse: '8 rue du Péronnet, 63390 Vernaison',
                clotureAt: '05/06/2026 à 15:21',
                messageAt: '06/06/2025 à 07:13',
                messageSuiviByNom: 'Abdallah',
                messageSuiviByPrenom: 'Karim',
                messageByProfileDeclarant: 'OCCUPANT'
            ),
            new TabDossier(
                nomDeclarant: 'Abdallah',
                prenomDeclarant: 'Karim',
                reference: '#2022-151',
                adresse: '9 rue du Péronnet, 63390 Vernaison',
                clotureAt: '05/06/2026 à 15:21',
                messageAt: '06/06/2025 à 07:13',
                messageSuiviByNom: 'Abdallah',
                messageSuiviByPrenom: 'Karim',
                messageByProfileDeclarant: 'TIERS DECLARANT'
            ),
        ];
    }

    /**
     * @return TabDossier[]
     */
    public function getMessagesUsagersMessagesSansReponse(?TabQueryParameters $tabQueryParameters = null): array
    {
        return [
            new TabDossier(
                nomDeclarant: 'Abdallah',
                prenomDeclarant: 'Karim',
                reference: '#2022-150',
                adresse: '8 rue du Péronnet, 63390 Vernaison',
                clotureAt: '05/06/2026 à 15:21',
                messageDaysAgo: 577,
                messageSuiviByNom: 'Abdallah',
                messageSuiviByPrenom: 'Karim',
                messageByProfileDeclarant: 'OCCUPANT'
            ),
            new TabDossier(
                nomDeclarant: 'Abdallah',
                prenomDeclarant: 'Karim',
                reference: '#2022-151',
                adresse: '9 rue du Péronnet, 63390 Vernaison',
                clotureAt: '05/06/2026 à 15:21',
                messageDaysAgo: 504,
                messageSuiviByNom: 'Abdallah',
                messageSuiviByPrenom: 'Karim',
                messageByProfileDeclarant: 'TIERS DECLARANT'
            ),
        ];
    }

    /**
     * @return TabDossier[]
     */
    public function getDossiersAVerifierSansActivitePartenaires(?TabQueryParameters $tabQueryParameters = null): array
    {
        return [
            new TabDossier(
                nomDeclarant: 'Abdallah',
                prenomDeclarant: 'Karim',
                reference: '#2022-150',
                adresse: '8 rue du Péronnet, 63390 Vernaison',
                derniereActionAt: '01/12/2023',
                derniereActionTypeSuivi: 'Suivi interne',
                derniereActionPartenaireDaysAgo: 497,
                derniereActionPartenaireNom: 'Commune de Vandoeuvre',
                derniereActionPartenaireNomAgent: 'Dumas',
                derniereActionPartenairePrenomAgent: 'Mireille',
            ),
            new TabDossier(
                nomDeclarant: 'Abdallah',
                prenomDeclarant: 'Karim',
                reference: '#2022-150',
                adresse: '8 rue du Péronnet, 63390 Vernaison',
                derniereActionAt: '01/12/2023',
                derniereActionTypeSuivi: 'Suivi interne',
                derniereActionPartenaireDaysAgo: 497,
                derniereActionPartenaireNom: 'Commune de Vandoeuvre',
                derniereActionPartenaireNomAgent: 'Dumas',
                derniereActionPartenairePrenomAgent: 'Mireille',
            ),
        ];
    }

    /**
     * @return TabDossier[]
     */
    public function getDossiersDemandesFermetureByUsager(?TabQueryParameters $tabQueryParameters = null): array
    {
        return [
            new TabDossier(
                nomDeclarant: 'Abdallah',
                prenomDeclarant: 'Karim',
                reference: '#2022-150',
                adresse: '8 rue du Péronnet, 63390 Vernaison',
                demandeFermetureUsagerDaysAgo: 497,
                demandeFermetureUsagerProfileDeclarant: 'OCCUPANT',
                demandeFermetureUsagerAt: '07/12/2023'
            ),
            new TabDossier(
                nomDeclarant: 'Abdallah',
                prenomDeclarant: 'Karim',
                reference: '#2022-150',
                adresse: '8 rue du Péronnet, 63390 Vernaison',
                demandeFermetureUsagerDaysAgo: 497,
                demandeFermetureUsagerProfileDeclarant: 'OCCUPANT',
                demandeFermetureUsagerAt: '07/12/2023'
            ),
        ];
    }

    /**
     * @return TabDossier[]
     */
    public function getDossiersRelanceSansReponse(?TabQueryParameters $tabQueryParameters = null): array
    {
        return [
            new TabDossier(
                nomDeclarant: 'Abdallah',
                prenomDeclarant: 'Karim',
                reference: '#2022-150',
                adresse: '8 rue du Péronnet, 63390 Vernaison',
                nbRelanceDossier: 14,
                premiereRelanceDossierAt: '17/12/2024',
                dernierSuiviPublicAt: '29/09/2024',
                dernierTypeSuivi: 'Suivi automatique',
            ),
            new TabDossier(
                nomDeclarant: 'Abdallah',
                prenomDeclarant: 'Karim',
                reference: '#2022-150',
                adresse: '8 rue du Péronnet, 63390 Vernaison',
                nbRelanceDossier: 14,
                premiereRelanceDossierAt: '17/12/2024',
                dernierSuiviPublicAt: '29/09/2024',
                dernierTypeSuivi: 'Suivi automatique',
            ),
        ];
    }

    /**
     * @return TabDossier[]
     */
    public function getDossiersFermePartenaireTous(?TabQueryParameters $tabQueryParameters = null): array
    {
        return [
            new TabDossier(
                nomDeclarant: 'Abdallah',
                prenomDeclarant: 'Karim',
                reference: '#2022-150',
                adresse: '8 rue du Péronnet, 63390 Vernaison',
                clotureAt: '17/12/2024',
            ),
            new TabDossier(
                nomDeclarant: 'Abdallah',
                prenomDeclarant: 'Karim',
                reference: '#2022-150',
                adresse: '8 rue du Péronnet, 63390 Vernaison',
                clotureAt: '17/12/2024',
            ),
        ];
    }
}
