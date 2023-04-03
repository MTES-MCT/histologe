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
            'Description',
            'Nom',
            'Prénom',
            'Téléphone',
            'Téléphone',
            'Email',
            'Adresse',
            'Code postal',
            'Commmune',
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
            'Nb adultes',
            'Nb enfants -6 ans',
            'Nb enfants +6 ans',
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
            'Structure tiers',
            'Lien tiers occupant',
            'Criticité au dépôt',
            'Visité le',
            'Occupant présent visite',
            'Dernière MAJ le',
            'Clôturé le',
            'Motif de clôture',
            'Criticité à la clôture',
        ];
    }
}
