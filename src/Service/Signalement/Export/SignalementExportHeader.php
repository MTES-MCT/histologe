<?php

namespace App\Service\Signalement\Export;

class SignalementExportHeader
{
    public const string SEPARATOR = ';';

    /**
     * @return array<string>
     *
     * @warning This array must stay in sync with the selectable columns defined in SignalementExportSelectableColumns::SELECTABLE_COLS
     */
    public static function getHeaders(): array
    {
        return [
            'Référence',
            'Déposé le',
            'Statut',
            'Description',
            'Type déclarant',
            'Nom occupant',
            'Prénom occupant',
            'Tél. occupant',
            'Téléphone sec.',
            'E-mail occupant',
            'Adresse',
            'Code postal',
            'Commune',
            'Code INSEE',
            'EPCI',
            'Étage',
            'Escalier',
            'Appartement',
            'Complément',
            'Situations',
            'Désordres',
            'Criticité au dépôt',
            'Début des désordres',
            'Étiquettes',
            'Photos',
            'Documents',
            'Nom bailleur',
            'Prénom bailleur',
            'Dénomination bailleur',
            'Adresse bailleur',
            'Code postal bailleur',
            'Ville bailleur',
            'Tél. bailleur',
            'Tél. sec. bailleur',
            'E-mail bailleur',
            'Propriétaire averti',
            'Date d\'information du propriétaire',
            'Moyen d\'information du propriétaire',
            'Nb personnes',
            'Nb enfants',
            'Enfants -6 ans',
            'Allocataire',
            'Numéro allocataire',
            'Nature du logement',
            'Superficie',
            'Logement social',
            'Préavis de départ',
            'Demande de relogement',
            'Déclarant tiers',
            'Nom tiers',
            'E-mail tiers',
            'Structure tiers',
            'Lien tiers occupant',
            'Nombre de visites',
            'Date de la dernière visite',
            'Occupant présent dernière visite',
            'Statut de la dernière visite',
            'Ccl. de la dernière visite',
            'Comm. de la dernière visite',
            'Dernière MAJ le',
            'Fermé le',
            'Motif de clôture',
            'Commentaire de clôture',
            'Longitude',
            'Latitude',
        ];
    }
}
