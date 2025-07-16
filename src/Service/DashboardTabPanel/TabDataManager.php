<?php

namespace App\Service\DashboardTabPanel;

use App\Entity\Enum\SuiviCategory;
use App\Entity\User;
use App\Repository\JobEventRepository;
use App\Repository\SuiviRepository;
use App\Repository\TerritoryRepository;
use App\Service\ListFilters\SearchInterconnexion;
use Symfony\Bundle\SecurityBundle\Security;

class TabDataManager
{
    public function __construct(
        private readonly Security $security,
        private readonly JobEventRepository $jobEventRepository,
        private readonly SuiviRepository $suiviRepository,
        private readonly TerritoryRepository $territoryRepository,
    ) {
    }

    /**
     * @return TabDossier[]
     */
    public function getDossierNonAffectation(?TabQueryParameters $tabQueryParameters = null): array
    {
        return [
            new TabDossier(
                profilDeclarant: 'Tiers déclarant',
                nomDeclarant: 'MARTINEZ',
                prenomDeclarant: 'Claude',
                reference: '#2022-150',
                adresse: '12 rue Saint-Ferréol, 13001 Marseille',
                depotAt: '24/04/2025',
                valideAt: '24/04/2025',
                validePartenaireBy: 'Ville de Vandoeuvre',
                parc: 'PRIVÉ',
                lien: '#'
            ),
        ];
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

    /**
     * @return array<string, bool|\DateTimeImmutable|string>
     */
    public function getInterconnexions(?TabQueryParameters $tabQueryParameters = null): array
    {
        $searchInterconnexion = new SearchInterconnexion();
        $searchInterconnexion->setOrderType('j.createdAt-DESC');
        $territory = null;
        if ($tabQueryParameters && $tabQueryParameters->territoireId) {
            $territory = $this->territoryRepository->find($tabQueryParameters->territoireId);
        }
        $searchInterconnexion->setTerritory($territory);

        $lastConnection = $this->jobEventRepository->findLastJobEventByTerritory(
            30,
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
            'firstErrorLastDayAt' => $hasErrorLastDay ? $lastErrorSynchro['createdAt']->format('d/m/Y à H:i') : 'N/A', // TODO : timezone ?
            'LastSyncAt' => $lastSynchro ? $lastSynchro['createdAt']->format('d/m/Y à H:i') : 'N/A', // TODO : timezone ?
        ];
    }

    /**
     * @return TabDossier[]
     */
    public function getDossiersFormPro(?TabQueryParameters $tabQueryParameters = null): array
    {
        return [
            new TabDossier(
                nomDeclarant: 'MARTINEZ',
                prenomDeclarant: 'Claude',
                reference: '#2022-150',
                adresse: '12 rue Saint-Ferréol, 13001 Marseille',
                depotAt: '24/04/2025',
                depotBy: 'MIREILLE DUMAS',
                depotPartenaireBy: 'Habitat 13',
                parc: 'PRIVÉ',
                lien: '#'
            ),
            new TabDossier(
                nomDeclarant: 'DUPUIS',
                prenomDeclarant: 'Marine',
                reference: '#2022-151',
                adresse: '85 boulevard Longchamp, 13004 Marseille',
                depotAt: '23/04/2025',
                depotBy: 'MIREILLE DUMAS',
                depotPartenaireBy: 'Habitat 13',
                parc: 'PRIVÉ',
                lien: '#'
            ),
            new TabDossier(
                nomDeclarant: 'BENOIT',
                prenomDeclarant: 'Julien',
                reference: '#2022-152',
                adresse: '5 avenue de la Capelette, 13010 Marseille',
                depotAt: '22/04/2025',
                depotBy: 'MIREILLE DUMAS',
                depotPartenaireBy: 'Habitat 13',
                parc: 'PUBLIC',
                lien: '#'
            ),
            new TabDossier(
                nomDeclarant: 'RICCI',
                prenomDeclarant: 'Paolo',
                reference: '#2022-153',
                adresse: '17 rue Paradis, 13006 Marseille',
                depotAt: '21/04/2025',
                depotBy: 'MIREILLE DUMAS',
                depotPartenaireBy: 'Habitat 13',
                parc: 'PRIVÉ',
                lien: '#'
            ),
        ];
    }

    /**
     * @return TabDossier[]
     */
    public function getDossiersFormUsager(?TabQueryParameters $tabQueryParameters = null): array
    {
        return [
            new TabDossier(
                profilDeclarant: 'PROPRIÉTAIRE OCCUPANT',
                nomDeclarant: 'MARTINEZ',
                prenomDeclarant: 'Claude',
                reference: '#2022-150',
                adresse: '12 rue Saint-Ferréol, 13001 Marseille',
                depotAt: '24/04/2025',
                parc: 'PRIVÉ',
                lien: '#'
            ),
            new TabDossier(
                profilDeclarant: 'LOCATAIRE',
                nomDeclarant: 'DUPUIS',
                prenomDeclarant: 'Marine',
                reference: '#2022-151',
                adresse: '85 boulevard Longchamp, 13004 Marseille',
                depotAt: '23/04/2025',
                parc: 'PRIVÉ',
                lien: '#'
            ),
            new TabDossier(
                profilDeclarant: 'TIERS PARTICULIER',
                nomDeclarant: 'BENOIT',
                prenomDeclarant: 'Julien',
                reference: '#2022-152',
                adresse: '5 avenue de la Capelette, 13010 Marseille',
                depotAt: '22/04/2025',
                parc: 'PUBLIC',
                lien: '#'
            ),
            new TabDossier(
                profilDeclarant: 'LOCATAIRE',
                nomDeclarant: 'RICCI',
                prenomDeclarant: 'Paolo',
                reference: '#2022-153',
                adresse: '17 rue Paradis, 13006 Marseille',
                depotAt: '21/04/2025',
                parc: 'PRIVÉ',
                lien: '#'
            ),
        ];
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
