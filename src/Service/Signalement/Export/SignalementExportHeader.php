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
            'Nom',
            'Prénom',
            'Téléphone',
            'Téléphone sec.',
            'Email',
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
            'Visité le',
            'Occupant présent visite',
            'Statut de la visite',
            'Dernière MAJ le',
            'Clôturé le',
            'Motif de clôture',
            'Géolocalisation',
        ];
    }
}
