<?php

namespace App\Service\Signalement\Export;

class SignalementExportHeader
{
    public const SEPARATOR = ';';

    public static function getHeaders(): array
    {
        return [
            'Référence',
            'Déposé le',
            'Statut',
            'Criticité au dépôt',
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
            'Étage',
            'Escalier',
            'Appartement',
            'Complément',
            'Situations',
            'Désordres',
            'Étiquettes',
            'Photos',
            'Documents',
            'Propriétaire averti',
            'Nb personnes',
            'Enfants -6 ans',
            'Allocataire',
            'Numéro allocataire',
            'Nature du logement',
            'Superficie',
            'Nom bailleur',
            'Logement social',
            'Préavis de départ',
            'Demande de relogement',
            'Déclarant tiers',
            'Nom tiers',
            'E-mail tiers',
            'Structure tiers',
            'Lien avec occupant',
            'Date de visite',
            'Occupant présent visite',
            'Statut de la visite',
            'Conclusion de la visite',
            'Commentaire de la visite',
            'Dernière MAJ le',
            'Clôturé le',
            'Motif de clôture',
            'Géolocalisation',
        ];
    }
}
