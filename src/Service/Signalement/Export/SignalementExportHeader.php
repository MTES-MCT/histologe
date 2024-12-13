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
            'Début des désordres',
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
            'Lien tiers occupant',
            'Nombre de visites',
            'Date de la dernière visite',
            'Occupant présent dernière visite',
            'Statut de la dernière visite',
            'Ccl. de la dernière visite',
            'Comm. de la dernière visite',
            'Dernière MAJ le',
            'Clôturé le',
            'Motif de clôture',
            'Longitude',
            'Latitude',
        ];
    }
}
