<?php

namespace App\Service\Signalement\Export;

class SignalementExportSelectableColumns
{
    private const SELECTABLE_COLS = [
        'TEL_SEC' => ['name' => 'Téléphone sec.', 'description' => 'Numéro de téléphone secondaire de l\'occupant du logement', 'export' => 'telephoneOccupantBis'],
        'INSEE' => ['name' => 'Code INSEE', 'description' => 'Le code INSEE de la commune du signalement', 'export' => 'inseeOccupant'],
        'ETAGE' => ['name' => 'Étage', 'description' => 'L\'étage du logement', 'export' => 'etageOccupant'],
        'ESCALIER' => ['name' => 'Escalier', 'description' => 'Le numéro d\'escalier du logement', 'export' => 'escalierOccupant'],
        'APPARTEMENT' => ['name' => 'Appartement', 'description' => 'Le numéro d\'appartement du logement', 'export' => 'numAppartOccupant'],
        'COMP_ADRESSE' => ['name' => 'Complément', 'description' => 'Le complément d\'adresse du logement', 'export' => 'adresseAutreOccupant'],
        'CRITICITE' => ['name' => 'Criticité au dépôt', 'description' => 'Score de criticité calculé automatiquement au dépôt du signalement', 'export' => 'score'],
        'ETIQUETTES' => ['name' => 'Étiquettes', 'description' => 'Les étiquettes ajoutées au signalement', 'export' => 'etiquettes'],
        'PHOTOS' => ['name' => 'Photos', 'description' => 'Le nom des fichiers photo ajoutés au signalement', 'export' => 'photos'],
        'DOCUMENTS' => ['name' => 'Documents', 'description' => 'Le nom des documents ajoutés au signalement', 'export' => 'documents'],
        'PROPRIETAIRE_AVERTI' => ['name' => 'Propriétaire averti', 'description' => 'Si le propriétaire a été averti ou non de la situation', 'export' => 'isProprioAverti'],
        'NB_PERSONNES' => ['name' => 'Nb personnes', 'description' => 'Le nombre de personnes occupant le logement', 'export' => 'nbPersonnes'],
        'MOINS_6_ANS' => ['name' => 'Enfants -6 ans', 'description' => 'Si oui ou non il y a des enfants de - de 6 ans dans le logement', 'export' => 'enfantsM6'],
        'NUM_ALLOCATAIRE' => ['name' => 'Numéro allocataire', 'description' => 'Le numéro d\'allocataire de l\'occupant', 'export' => 'numAllocataire'],
        'NATURE_LOGEMENT' => ['name' => 'Nature du logement', 'description' => 'La nature du logement (maison, appartement, autre)', 'export' => 'natureLogement'],
        'SUPERFICIE' => ['name' => 'Superficie', 'description' => 'La superficie du logement en m²', 'export' => 'superficie'],
        'NOM_BAILLEUR' => ['name' => 'Nom bailleur', 'description' => 'Le nom du bailleur du logement', 'export' => 'nomProprio'],
        'PREAVIS_DEPART' => ['name' => 'Préavis de départ', 'description' => 'Si le foyer a déposé un préavis de départ ou non', 'export' => 'isPreavisDepart'],
        'DEMANDE_RELOGEMENT' => ['name' => 'Demande de relogement', 'description' => 'Si le foyer a fait une demande de relogement ou non', 'export' => 'isRelogement'],
        'DECLARANT_TIERS' => ['name' => 'Déclarant tiers', 'description' => 'Si le signalement a été déposé par un tiers ou non', 'export' => 'isNotOccupant'],
        'NOM_TIERS' => ['name' => 'Nom tiers', 'description' => 'Le nom du tiers déclarant', 'export' => 'nomDeclarant'],
        'EMAIL_TIERS' => ['name' => 'E-mail tiers', 'description' => 'L\'adresse e-mail du tiers déclarant', 'export' => 'emailDeclarant'],
        'STRUCTURE_TIERS' => ['name' => 'Structure tiers', 'description' => 'La structure du tiers déclarant', 'export' => 'structureDeclarant'],
        'LIEN_TIERS' => ['name' => 'Lien tiers occupant', 'description' => 'Le lien du tiers déclarant avec l\'occupant (voisin, proche, pro...)', 'export' => 'lienDeclarantOccupant'],
        'STATUT_VISITE' => ['name' => 'Statut de la visite', 'description' => 'Le statut de la visite (planifiée, terminée, à planifier...)', 'export' => 'interventionStatus'],
        'DATE_VISITE' => ['name' => 'Date de visite', 'description' => 'La date de visite du logement', 'export' => 'dateVisite'],
        'OCCUPANT_PRESENT_VISITE' => ['name' => 'Occupant présent visite', 'description' => 'Si l\'occupant était présent pendant la visite ou non', 'export' => 'isOccupantPresentVisite'],
        'CONCLUSION_VISITE' => ['name' => 'Conclusion de la visite', 'description' => 'La conclusion de la visite (procédures constatées)', 'export' => 'interventionConcludeProcedure'],
        'COMMENTAIRE_VISITE' => ['name' => 'Commentaire de la visite', 'description' => 'Le commentaire laissé par l\'opérateur suite à la visite', 'export' => 'interventionDetails'],
        'DERNIERE_MAJ' => ['name' => 'Dernière MAJ le', 'description' => 'La date de la dernière mise à jour du signalement', 'export' => 'modifiedAt'],
        'DATE_CLOTURE' => ['name' => 'Clôturé le', 'description' => 'La date de clôture du signalement', 'export' => 'closedAt'],
        'MOTIF_CLOTURE' => ['name' => 'Motif de clôture', 'description' => 'Le motif de clôture du signalement', 'export' => 'motifCloture'],
        'GEOLOCALISATION' => ['name' => 'Géolocalisation', 'description' => 'Les coordonnées GPS du logement', 'export' => 'geoloc'],
    ];

    public static function getColumns(): array
    {
        return self::SELECTABLE_COLS;
    }
}
