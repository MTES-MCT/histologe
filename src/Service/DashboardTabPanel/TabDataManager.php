<?php

namespace App\Service\DashboardTabPanel;

class TabDataManager
{
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
        $tabDossiers = [];
        for ($i = 0; $i < 10; ++$i) {
            $tabDossiers[] = new TabDossier(
                nomDeclarant: 'MOREAU',
                prenomDeclarant: 'Samuel',
                reference: '#2024-'.rand(1, 1000),
                adresse: '4 impasse des Lilas, 13002 Marseille',
                statut: 'En cours',
                derniereAction: 'Relance partenaire',
                derniereActionAt: '08/04/2025',
                actionDepuis: 'OUI',
                lien: '#',
            );
        }

        return $tabDossiers;
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

    /**
     * @return array<string>
     */
    public function getEmptyData(): array
    {
        return [];
    }
}
