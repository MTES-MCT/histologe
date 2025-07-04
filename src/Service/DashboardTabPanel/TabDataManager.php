<?php

namespace App\Service\DashboardTabPanel;

class TabDataManager
{
    /**
     * @return TabDossier[]
     */
    public function getDossierNonAffectation(): array
    {
        return [
            new TabDossier(
                profilDeclarant: 'Tiers déclarant',
                reference: '#2022-150 - MARTINEZ Claude',
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
    public function getDernierActionDossiers(): array
    {
        return [
            new TabDossier(
                reference: '#2024-1525 - MOREAU Samuel',
                adresse: '4 impasse des Lilas, 13002 Marseille',
                statut: 'En cours',
                derniereAction: 'Relance partenaire',
                derniereActionAt: '08/04/2025',
                actionDepuis: 'OUI',
                lien: '#',
            ),
            new TabDossier(
                reference: '#2024-1526 - DUPONT Jean',
                adresse: '3 rue Victor Hugo, 13003 Marseille',
                statut: 'En attente',
                derniereAction: 'Vérification des pièces',
                derniereActionAt: '07/04/2025',
                actionDepuis: 'NON',
                lien: '#'
            ),
        ];
    }

    /**
     * @return TabDossier[]
     */
    public function getDossiersFormPro(): array
    {
        return [
            new TabDossier(
                reference: '#2022-150 - MARTINEZ Claude',
                adresse: '12 rue Saint-Ferréol, 13001 Marseille',
                depotAt: '24/04/2025',
                depotBy: 'MIREILLE DUMAS',
                depotPartenaireBy: 'Habitat 13',
                parc: 'PRIVÉ',
                lien: '#'
            ),
            new TabDossier(
                reference: '#2022-151 - DUPUIS Marine',
                adresse: '85 boulevard Longchamp, 13004 Marseille',
                depotAt: '23/04/2025',
                depotBy: 'MIREILLE DUMAS',
                depotPartenaireBy: 'Habitat 13',
                parc: 'PRIVÉ',
                lien: '#'
            ),
            new TabDossier(
                reference: '#2022-152 - BENOIT Julien',
                adresse: '5 avenue de la Capelette, 13010 Marseille',
                depotAt: '22/04/2025',
                depotBy: 'MIREILLE DUMAS',
                depotPartenaireBy: 'Habitat 13',
                parc: 'PUBLIC',
                lien: '#'
            ),
            new TabDossier(
                reference: '#2022-153 - RICCI Paolo',
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
    public function getDossiersFormUsager(): array
    {
        return [
            new TabDossier(
                profilDeclarant: 'PROPRIÉTAIRE OCCUPANT',
                reference: '#2022-150 - MARTINEZ Claude',
                adresse: '12 rue Saint-Ferréol, 13001 Marseille',
                depotAt: '24/04/2025',
                parc: 'PRIVÉ',
                lien: '#'
            ),
            new TabDossier(
                profilDeclarant: 'LOCATAIRE',
                reference: '#2022-151 - DUPUIS Marine',
                adresse: '85 boulevard Longchamp, 13004 Marseille',
                depotAt: '23/04/2025',
                parc: 'PRIVÉ',
                lien: '#'
            ),
            new TabDossier(
                profilDeclarant: 'TIERS PARTICULIER',
                reference: '#2022-152 - BENOIT Julien',
                adresse: '5 avenue de la Capelette, 13010 Marseille',
                depotAt: '22/04/2025',
                parc: 'PUBLIC',
                lien: '#'
            ),
            new TabDossier(
                profilDeclarant: 'LOCATAIRE',
                reference: '#2022-153 - RICCI Paolo',
                adresse: '17 rue Paradis, 13006 Marseille',
                depotAt: '21/04/2025',
                parc: 'PRIVÉ',
                lien: '#'
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
