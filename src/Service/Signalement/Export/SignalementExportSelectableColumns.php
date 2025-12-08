<?php

namespace App\Service\Signalement\Export;

class SignalementExportSelectableColumns
{
    /**
     * @var array<mixed> SELECTABLE_COLS
     */
    private const array SELECTABLE_COLS = [
        'REFERENCE' => ['name' => 'Référence', 'description' => 'La référence du signalement', 'export' => 'reference'],
        'CREATED_AT' => ['name' => 'Déposé le', 'description' => 'La date de dépôt du signalement', 'export' => 'createdAt'],
        'STATUT' => ['name' => 'Statut', 'description' => 'Le statut du signalement (nouveau, en cours, fermé)', 'export' => 'statut'],
        'DESCRIPTION' => ['name' => 'Description', 'description' => 'Le message laissé par l\'usager au dépôt du signalement, décrivant la situation', 'export' => 'description'],
        'TYPE_DECLARANT' => ['name' => 'Type déclarant', 'description' => 'Le type de déclarant (occupant, tiers, propriétaire occupant, services de secours...)', 'export' => 'typeDeclarant'],
        'NOM_OCCUPANT' => ['name' => 'Nom occupant', 'description' => 'Le nom de l\'occupant du logement', 'export' => 'nomOccupant'],
        'PRENOM_OCCUPANT' => ['name' => 'Prénom occupant', 'description' => 'Le prénom de l\'occupant du logement', 'export' => 'prenomOccupant'],
        'TELEPHONE_OCCUPANT' => ['name' => 'Tél. occupant', 'description' => 'Le numéro de téléphone de l\'occupant', 'export' => 'telephoneOccupant'],
        'EMAIL_OCCUPANT' => ['name' => 'E-mail occupant', 'description' => 'L\'adresse e-mail de l\'occupant', 'export' => 'emailOccupant'],
        'ADRESSE_OCCUPANT' => ['name' => 'Adresse', 'description' => 'L\'adresse postale du logement', 'export' => 'adresseOccupant'],
        'CP_OCCUPANT' => ['name' => 'Code postal', 'description' => 'Le code postal du logement', 'export' => 'cpOccupant'],
        'VILLE_OCCUPANT' => ['name' => 'Commune', 'description' => 'La commune du logement', 'export' => 'villeOccupant'],
        'SITUATIONS' => ['name' => 'Situations', 'description' => 'Les catégories de désordres présents dans le logement', 'export' => 'situations'],
        'DESORDRES' => ['name' => 'Désordres', 'description' => 'Le détail des désordres présents dans le logement', 'export' => 'desordres'],
        'IS_ALLOCATAIRE' => ['name' => 'Allocataire', 'description' => 'Si l\'usager est allocataire ou non', 'export' => 'isAllocataire'],
        'IS_LOGEMENT_SOCIAL' => ['name' => 'Logement social', 'description' => 'S\'il s\'agit d\'un logement social ou non', 'export' => 'isLogementSocial'],
        'TEL_SEC' => ['name' => 'Téléphone sec.', 'description' => 'Numéro de téléphone secondaire de l\'occupant du logement', 'export' => 'telephoneOccupantBis'],
        'INSEE' => ['name' => 'Code INSEE', 'description' => 'Le code INSEE de la commune du signalement', 'export' => 'inseeOccupant'],
        'EPCI_NOM' => ['name' => 'EPCI', 'description' => 'L\'EPCI auquel appartient la commune du logement', 'export' => 'epciNom'],
        'ETAGE' => ['name' => 'Étage', 'description' => 'L\'étage du logement', 'export' => 'etageOccupant'],
        'ESCALIER' => ['name' => 'Escalier', 'description' => 'Le numéro d\'escalier du logement', 'export' => 'escalierOccupant'],
        'APPARTEMENT' => ['name' => 'Appartement', 'description' => 'Le numéro d\'appartement du logement', 'export' => 'numAppartOccupant'],
        'COMP_ADRESSE' => ['name' => 'Complément', 'description' => 'Le complément d\'adresse du logement', 'export' => 'adresseAutreOccupant'],
        'CRITICITE' => ['name' => 'Criticité au dépôt', 'description' => 'Score de criticité calculé automatiquement au dépôt du signalement', 'export' => 'score'],
        'ETIQUETTES' => ['name' => 'Étiquettes', 'description' => 'Les étiquettes ajoutées au signalement', 'export' => 'etiquettes'],
        'PHOTOS' => ['name' => 'Photos', 'description' => 'Le nom des fichiers photo ajoutés au signalement', 'export' => 'photos'],
        'DOCUMENTS' => ['name' => 'Documents', 'description' => 'Le nom des documents ajoutés au signalement', 'export' => 'documents'],
        'NOM_PROPRIO' => ['name' => 'Nom bailleur', 'description' => 'Le nom du bailleur du logement', 'export' => 'nomProprio'],
        'PRENOM_PROPRIO' => ['name' => 'Prénom bailleur', 'description' => 'Le prénom du bailleur du logement', 'export' => 'prenomProprio'],
        'DENOMINATION_PROPRIO' => ['name' => 'Dénomination bailleur', 'description' => 'La dénomination du bailleur du logement', 'export' => 'denominationProprio'],
        'ADRESSE_PROPRIO' => ['name' => 'Adresse bailleur', 'description' => 'L\'adresse postale du bailleur du logement', 'export' => 'adresseProprio'],
        'CP_PROPRIO' => ['name' => 'Code postal bailleur', 'description' => 'Le code postal du bailleur du logement', 'export' => 'codePostalProprio'],
        'VILLE_PROPRIO' => ['name' => 'Ville bailleur', 'description' => 'La commune du bailleur du logement', 'export' => 'villeProprio'],
        'TEL_PROPRIO' => ['name' => 'Tél. bailleur', 'description' => 'Le numéro de téléphone secondaire du bailleur du logement', 'export' => 'telProprio'],
        'TEL_BIS_PROPRIO' => ['name' => 'Tél. sec. bailleur', 'description' => 'Le numéro de téléphone secondaire du bailleur du logement', 'export' => 'telProprioSecondaire'],
        'MAIL_PROPRIO' => ['name' => 'E-mail bailleur', 'description' => 'L\'adresse e-mail du bailleur du logement', 'export' => 'mailProprio'],
        'PROPRIETAIRE_AVERTI' => ['name' => 'Propriétaire averti', 'description' => 'Si le propriétaire a été averti ou non de la situation', 'export' => 'isProprioAverti'],
        'PROPRIETAIRE_AVERTI_DATE' => ['name' => 'Date d\'information du propriétaire', 'description' => 'A quelle date le propriétaire a été averti de la situation', 'export' => 'infoProcedureBailDate'],
        'PROPRIETAIRE_AVERTI_MOYEN' => ['name' => 'Moyen d\'information du propriétaire', 'description' => 'Par quel moyen le propriétaire a été averti de la situation', 'export' => 'infoProcedureBailMoyen'],
        'NB_PERSONNES' => ['name' => 'Nb personnes', 'description' => 'Le nombre de personnes total occupant le logement', 'export' => 'nbPersonnes'],
        'NB_ENFANTS' => ['name' => 'Nb enfants', 'description' => 'Le nombre d\'enfants occupant le logement', 'export' => 'nbEnfants'],
        'MOINS_6_ANS' => ['name' => 'Enfants -6 ans', 'description' => 'Si oui ou non il y a des enfants de - de 6 ans dans le logement', 'export' => 'enfantsM6'],
        'NUM_ALLOCATAIRE' => ['name' => 'Numéro allocataire', 'description' => 'Le numéro d\'allocataire de l\'occupant', 'export' => 'numAllocataire'],
        'NATURE_LOGEMENT' => ['name' => 'Nature du logement', 'description' => 'La nature du logement (maison, appartement, autre)', 'export' => 'natureLogement'],
        'SUPERFICIE' => ['name' => 'Superficie', 'description' => 'La superficie du logement en m²', 'export' => 'superficie'],
        'PREAVIS_DEPART' => ['name' => 'Préavis de départ', 'description' => 'Si le foyer a déposé un préavis de départ ou non', 'export' => 'isPreavisDepart'],
        'DEMANDE_RELOGEMENT' => ['name' => 'Demande de relogement', 'description' => 'Si le foyer a fait une demande de relogement ou non', 'export' => 'isRelogement'],
        'DECLARANT_TIERS' => ['name' => 'Déclarant tiers', 'description' => 'Si le signalement a été déposé par un tiers ou non', 'export' => 'isNotOccupant'],
        'NOM_TIERS' => ['name' => 'Nom tiers', 'description' => 'Le nom du tiers déclarant', 'export' => 'nomDeclarant'],
        'EMAIL_TIERS' => ['name' => 'E-mail tiers', 'description' => 'L\'adresse e-mail du tiers déclarant', 'export' => 'emailDeclarant'],
        'STRUCTURE_TIERS' => ['name' => 'Structure tiers', 'description' => 'La structure du tiers déclarant', 'export' => 'structureDeclarant'],
        'LIEN_TIERS' => ['name' => 'Lien tiers occupant', 'description' => 'Le lien du tiers déclarant avec l\'occupant (voisin, proche, pro...)', 'export' => 'lienDeclarantOccupant'],
        'NB_VISITES' => ['name' => 'Nombre de visites', 'description' => 'Le nombre de visites enregistrée sur le logement', 'export' => 'nbVisites'],
        'STATUT_VISITE' => ['name' => 'Statut de la dernière visite', 'description' => 'Le statut de la dernière visite (planifiée, terminée, à planifier...)', 'export' => 'interventionStatus'],
        'DATE_VISITE' => ['name' => 'Date de la dernière visite', 'description' => 'La date de la dernière visite du logement', 'export' => 'dateVisite'],
        'OCCUPANT_PRESENT_VISITE' => ['name' => 'Occupant présent dernière visite', 'description' => 'Si l\'occupant était présent ou non pendant la dernière visite', 'export' => 'isOccupantPresentVisite'],
        'CONCLUSION_VISITE' => ['name' => 'Ccl. de la dernière visite', 'description' => 'La conclusion de la dernière visite (procédures constatées)', 'export' => 'interventionConcludeProcedure'],
        'COMMENTAIRE_VISITE' => ['name' => 'Comm. de la dernière visite', 'description' => 'Le commentaire laissé par l\'opérateur suite à la dernière visite', 'export' => 'interventionDetails'],
        'DERNIERE_MAJ' => ['name' => 'Dernière MAJ le', 'description' => 'La date de la dernière mise à jour du signalement', 'export' => 'modifiedAt'],
        'DATE_CLOTURE' => ['name' => 'Fermé le', 'description' => 'La date de clôture du signalement', 'export' => 'closedAt'],
        'MOTIF_CLOTURE' => ['name' => 'Motif de clôture', 'description' => 'Le motif de clôture du signalement', 'export' => 'motifCloture'],
        'COM_CLOTURE' => ['name' => 'Commentaire de clôture', 'description' => 'Le commentaire de clôture du signalement', 'export' => 'comCloture'],
        'GEOLOCALISATION' => ['name' => 'Géolocalisation', 'description' => 'Les coordonnées GPS du logement', 'export' => 'geoloc'],
        'DEBUT_DESORDRES' => ['name' => 'Début des désordres', 'description' => 'Début des désordres', 'export' => 'debutDesordres'],
    ];

    /**
     * @return array<mixed>
     */
    public static function getColumns(): array
    {
        return self::SELECTABLE_COLS;
    }
}
