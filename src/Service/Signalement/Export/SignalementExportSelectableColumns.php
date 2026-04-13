<?php

namespace App\Service\Signalement\Export;

class SignalementExportSelectableColumns
{
    /**
     * @var array<mixed> SELECTABLE_COLS
     *
     * @warning This array must stay in sync with the headers defined in SignalementExportHeader::getHeaders()
     */
    private const array SELECTABLE_COLS = [
        'REFERENCE' => ['name' => 'Référence', 'description' => 'La référence du signalement', 'export' => 's.reference', 'preselected' => true],
        'CREATED_AT' => ['name' => 'Déposé le', 'description' => 'La date de dépôt du signalement', 'export' => 's.createdAt', 'preselected' => true],
        'STATUT' => ['name' => 'Statut', 'description' => 'Le statut du signalement (nouveau, en cours, fermé)', 'export' => 's.statut', 'preselected' => true],
        'DESCRIPTION' => ['name' => 'Description', 'description' => 'Le message laissé par l\'usager au dépôt du signalement, décrivant la situation', 'export' => 's.details', 'preselected' => true],
        'TYPE_DECLARANT' => ['name' => 'Type déclarant', 'description' => 'Le type de déclarant (occupant, tiers, propriétaire occupant, services de secours...)', 'export' => 's.profileDeclarant', 'preselected' => true],
        'NOM_OCCUPANT' => ['name' => 'Nom occupant', 'description' => 'Le nom de l\'occupant du logement', 'export' => 's.nomOccupant', 'preselected' => true],
        'PRENOM_OCCUPANT' => ['name' => 'Prénom occupant', 'description' => 'Le prénom de l\'occupant du logement', 'export' => 's.prenomOccupant', 'preselected' => true],
        'TELEPHONE_OCCUPANT' => ['name' => 'Tél. occupant', 'description' => 'Le numéro de téléphone de l\'occupant', 'export' => 's.telOccupant', 'preselected' => true],
        'TEL_SEC' => ['name' => 'Téléphone sec.', 'description' => 'Numéro de téléphone secondaire de l\'occupant du logement', 'export' => 's.telOccupantBis', 'preselected' => false],
        'EMAIL_OCCUPANT' => ['name' => 'E-mail occupant', 'description' => 'L\'adresse e-mail de l\'occupant', 'export' => 's.mailOccupant', 'preselected' => true],
        'ADRESSE_OCCUPANT' => ['name' => 'Adresse', 'description' => 'L\'adresse postale du logement', 'export' => 's.adresseOccupant', 'preselected' => true],
        'CP_OCCUPANT' => ['name' => 'Code postal', 'description' => 'Le code postal du logement', 'export' => 's.cpOccupant', 'preselected' => true],
        'VILLE_OCCUPANT' => ['name' => 'Commune', 'description' => 'La commune du logement', 'export' => 's.villeOccupant', 'preselected' => true],
        'INSEE' => ['name' => 'Code INSEE', 'description' => 'Le code INSEE de la commune du signalement', 'export' => 's.inseeOccupant', 'preselected' => false],
        'EPCI_NOM' => ['name' => 'EPCI', 'description' => 'L\'EPCI auquel appartient la commune du logement', 'export' => 'e.nom', 'preselected' => false],
        'ETAGE' => ['name' => 'Étage', 'description' => 'L\'étage du logement', 'export' => 's.etageOccupant', 'preselected' => false],
        'ESCALIER' => ['name' => 'Escalier', 'description' => 'Le numéro d\'escalier du logement', 'export' => 's.escalierOccupant', 'preselected' => false],
        'APPARTEMENT' => ['name' => 'Appartement', 'description' => 'Le numéro d\'appartement du logement', 'export' => 's.numAppartOccupant', 'preselected' => false],
        'COMP_ADRESSE' => ['name' => 'Complément', 'description' => 'Le complément d\'adresse du logement', 'export' => 's.adresseAutreOccupant', 'preselected' => false],
        'PROFILE_OCCUPANT' => ['name' => 'Type occupant', 'description' => 'Le type d\'occupant du logement (locataire, propriétaire)', 'export' => 's.profileOccupant', 'preselected' => false],
        'SITUATIONS' => ['name' => 'Situations', 'description' => 'Les catégories de désordres présents dans le logement', 'export' => 'situations', 'preselected' => true, 'specificSelect' => true],
        'DESORDRES' => ['name' => 'Désordres', 'description' => 'Le détail des désordres présents dans le logement', 'export' => 'desordres', 'preselected' => true, 'specificSelect' => true],
        'CRITICITE' => ['name' => 'Criticité au dépôt', 'description' => 'Score de criticité calculé automatiquement au dépôt du signalement', 'export' => 's.score', 'preselected' => false],
        'DEBUT_DESORDRES' => ['name' => 'Début des désordres', 'description' => 'Début des désordres', 'export' => 's.debutDesordres', 'preselected' => false],
        'ETIQUETTES' => ['name' => 'Étiquettes', 'description' => 'Les étiquettes ajoutées au signalement', 'export' => 's.etiquettes', 'preselected' => false, 'specificSelect' => true],
        'PHOTOS' => ['name' => 'Photos', 'description' => 'Le nom des fichiers photo ajoutés au signalement', 'export' => 's.photos', 'preselected' => false, 'specificSelect' => true],
        'DOCUMENTS' => ['name' => 'Documents', 'description' => 'Le nom des documents ajoutés au signalement', 'export' => 's.documents', 'preselected' => false, 'specificSelect' => true],
        'NOM_PROPRIO' => ['name' => 'Nom bailleur', 'description' => 'Le nom du bailleur du logement', 'export' => 's.nomProprio', 'preselected' => false],
        'PRENOM_PROPRIO' => ['name' => 'Prénom bailleur', 'description' => 'Le prénom du bailleur du logement', 'export' => 's.prenomProprio', 'preselected' => false],
        'DENOMINATION_PROPRIO' => ['name' => 'Dénomination bailleur', 'description' => 'La dénomination du bailleur du logement', 'export' => 's.denominationProprio', 'preselected' => false],
        'ADRESSE_PROPRIO' => ['name' => 'Adresse bailleur', 'description' => 'L\'adresse postale du bailleur du logement', 'export' => 's.adresseProprio', 'preselected' => false],
        'CP_PROPRIO' => ['name' => 'Code postal bailleur', 'description' => 'Le code postal du bailleur du logement', 'export' => 's.codePostalProprio', 'preselected' => false],
        'VILLE_PROPRIO' => ['name' => 'Ville bailleur', 'description' => 'La commune du bailleur du logement', 'export' => 's.villeProprio', 'preselected' => false],
        'TEL_PROPRIO' => ['name' => 'Tél. bailleur', 'description' => 'Le numéro de téléphone secondaire du bailleur du logement', 'export' => 's.telProprio', 'preselected' => false],
        'TEL_BIS_PROPRIO' => ['name' => 'Tél. sec. bailleur', 'description' => 'Le numéro de téléphone secondaire du bailleur du logement', 'export' => 's.telProprioSecondaire', 'preselected' => false],
        'MAIL_PROPRIO' => ['name' => 'E-mail bailleur', 'description' => 'L\'adresse e-mail du bailleur du logement', 'export' => 's.mailProprio', 'preselected' => false],
        'PROPRIETAIRE_AVERTI' => ['name' => 'Propriétaire averti', 'description' => 'Si le propriétaire a été averti ou non de la situation', 'export' => 's.isProprioAverti', 'preselected' => false],
        'PROPRIETAIRE_AVERTI_DATE' => ['name' => 'Date d\'information du propriétaire', 'description' => 'A quelle date le propriétaire a été averti de la situation', 'export' => 's.proprioAvertiAt', 'preselected' => false],
        'PROPRIETAIRE_AVERTI_MOYEN' => ['name' => 'Moyen d\'information du propriétaire', 'description' => 'Par quel moyen le propriétaire a été averti de la situation', 'export' => 's.informationProcedure', 'preselected' => false],
        'NB_PERSONNES' => ['name' => 'Nb personnes', 'description' => 'Le nombre de personnes total occupant le logement', 'export' => 's.nbOccupantsLogement', 'preselected' => false],
        'NB_ENFANTS' => ['name' => 'Nb enfants', 'description' => 'Le nombre d\'enfants occupant le logement', 'export' => 'nbEnfants', 'preselected' => false, 'specificSelect' => true],
        'MOINS_6_ANS' => ['name' => 'Enfants -6 ans', 'description' => 'Si oui ou non il y a des enfants de - de 6 ans dans le logement', 'export' => 'nbEnfantsM6', 'preselected' => false, 'specificSelect' => true],
        'AUTRE_SITUATION_VULNERABILITE' => ['name' => 'Autre situation de vulnérabilité', 'description' => 'Autre situation de vulnérabilité à mentionner', 'export' => 's.autreSituationVulnerabilite', 'preselected' => false],
        'IS_ALLOCATAIRE' => ['name' => 'Allocataire', 'description' => 'Si l\'usager est allocataire ou non', 'export' => 's.isAllocataire', 'preselected' => true],
        'NUM_ALLOCATAIRE' => ['name' => 'Numéro allocataire', 'description' => 'Le numéro d\'allocataire de l\'occupant', 'export' => 's.numAllocataire', 'preselected' => false],
        'NATURE_LOGEMENT' => ['name' => 'Nature du logement', 'description' => 'La nature du logement (maison, appartement, autre)', 'export' => 's.natureLogement', 'preselected' => false],
        'AUTRES_OCCUPANTS_DESORDRE' => ['name' => 'Autres occupants désordres', 'description' => 'Autres occupants de l’immeuble ayant rencontré des désordres', 'export' => 's.autresOccupantsDesordre', 'preselected' => false],
        'SUPERFICIE' => ['name' => 'Superficie', 'description' => 'La superficie du logement en m²', 'export' => 's.superficie', 'preselected' => false],
        'IS_LOGEMENT_SOCIAL' => ['name' => 'Logement social', 'description' => 'S\'il s\'agit d\'un logement social ou non', 'export' => 's.isLogementSocial', 'preselected' => true],
        'PREAVIS_DEPART' => ['name' => 'Préavis de départ', 'description' => 'Si le foyer a déposé un préavis de départ ou non', 'export' => 's.isPreavisDepart', 'preselected' => false],
        'DEMANDE_RELOGEMENT' => ['name' => 'Demande de relogement', 'description' => 'Si le foyer a fait une demande de relogement ou non', 'export' => 's.isRelogement', 'preselected' => false],
        'DECLARANT_TIERS' => ['name' => 'Déclarant tiers', 'description' => 'Si le signalement a été déposé par un tiers ou non', 'export' => 's.isNotOccupant', 'preselected' => false],
        'NOM_TIERS' => ['name' => 'Nom tiers', 'description' => 'Le nom du tiers déclarant', 'export' => 's.nomDeclarant', 'preselected' => false],
        'MATRICULE_TIERS' => ['name' => 'Matricule tiers', 'description' => 'Le matricule du tiers déclarant', 'export' => 's.matriculeDeclarant', 'preselected' => false],
        'EMAIL_TIERS' => ['name' => 'E-mail tiers', 'description' => 'L\'adresse e-mail du tiers déclarant', 'export' => 's.mailDeclarant', 'preselected' => false],
        'STRUCTURE_TIERS' => ['name' => 'Structure tiers', 'description' => 'La structure du tiers déclarant', 'export' => 's.structureDeclarant', 'preselected' => false],
        'LIEN_TIERS' => ['name' => 'Lien tiers occupant', 'description' => 'Le lien du tiers déclarant avec l\'occupant (voisin, proche, pro...)', 'export' => 's.lienDeclarantOccupant', 'preselected' => false],
        'NB_VISITES' => ['name' => 'Nombre de visites', 'description' => 'Le nombre de visites enregistrée sur le logement', 'export' => 'nbVisites', 'preselected' => false, 'specificSelect' => true],
        'DATE_VISITE' => ['name' => 'Date de la dernière visite', 'description' => 'La date de la dernière visite du logement', 'export' => 'dateVisite', 'preselected' => false, 'specificSelect' => true],
        'OCCUPANT_PRESENT_VISITE' => ['name' => 'Occupant présent dernière visite', 'description' => 'Si l\'occupant était présent ou non pendant la dernière visite', 'export' => 'isOccupantPresentVisite', 'preselected' => false, 'specificSelect' => true],
        'STATUT_VISITE' => ['name' => 'Statut de la dernière visite', 'description' => 'Le statut de la dernière visite (planifiée, terminée, à planifier...)', 'export' => 'interventionStatus', 'preselected' => false, 'specificSelect' => true],
        'CONCLUSION_VISITE' => ['name' => 'Ccl. de la dernière visite', 'description' => 'La conclusion de la dernière visite (procédures constatées)', 'export' => 'interventionConcludeProcedure', 'preselected' => false, 'specificSelect' => true],
        'COMMENTAIRE_VISITE' => ['name' => 'Comm. de la dernière visite', 'description' => 'Le commentaire laissé par l\'opérateur suite à la dernière visite', 'export' => 'interventionDetails', 'preselected' => false, 'specificSelect' => true],
        'DERNIERE_MAJ' => ['name' => 'Dernière MAJ le', 'description' => 'La date de la dernière mise à jour du signalement', 'export' => 's.modifiedAt', 'preselected' => false],
        'DATE_CLOTURE' => ['name' => 'Fermé le', 'description' => 'La date de clôture du signalement', 'export' => 's.closedAt', 'preselected' => false],
        'MOTIF_CLOTURE' => ['name' => 'Motif de clôture', 'description' => 'Le motif de clôture du signalement', 'export' => 's.motifCloture', 'preselected' => false],
        'COM_CLOTURE' => ['name' => 'Commentaire de clôture', 'description' => 'Le commentaire de clôture du signalement', 'export' => 's.comCloture', 'preselected' => false],
        'GEOLOCALISATION' => ['name' => 'Géolocalisation', 'description' => 'Les coordonnées GPS du logement', 'export' => 's.geoloc', 'preselected' => false],
    ];

    /**
     * @return array<mixed>
     */
    public static function getColumns(): array
    {
        return self::SELECTABLE_COLS;
    }
}
