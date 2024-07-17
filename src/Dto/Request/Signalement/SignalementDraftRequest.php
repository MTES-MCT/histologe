<?php

namespace App\Dto\Request\Signalement;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;

#[AppAssert\IsTerritoryActive]
class SignalementDraftRequest
{
    public const PREFIX_PROPERTIES_TYPE_COMPOSITION = ['type_logement', 'composition_logement', 'bail_dpe', 'desordres_logement_chauffage_details_dpe'];
    public const PREFIX_PROPERTIES_SITUATION_FOYER = ['logement_social', 'travailleur_social'];
    public const PREFIX_PROPERTIES_INFORMATION_PROCEDURE = ['info_procedure', 'utilisation_service'];
    public const PREFIX_PROPERTIES_INFORMATION_COMPLEMENTAIRE = ['informations_complementaires'];

    public const PATTERN_PHONE_KEY = '/.*(_tel|_tel_secondaire)$/';

    public const PATTERN_FILE_UPLOAD = '/\w+_upload/';
    public const PATTERN_NOMBRE = '/_nb_|_nombre_/';
    public const FILE_UPLOAD_KEY = 'files';

    #[Assert\Choice(choices: [
        'locataire',
        'bailleur_occupant',
        'bailleur',
        'tiers_pro',
        'tiers_particulier',
        'service_secours', ],
        message: 'Le profil est incorrect.'
    )]
    #[Assert\When(
        expression: 'this.getCurrentStep() == "validation_signalement"',
        constraints: [
            new Assert\NotBlank(message: 'Le profil est obligatoire.'),
        ],
    )]
    private ?string $profil = null;

    #[Assert\Length(max: 128, maxMessage: 'Le nom d\'étape de formulaire ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $currentStep = null;
    #[Assert\NotBlank(message: 'Merci de saisir une adresse.')]
    #[Assert\Length(max: 255, maxMessage: 'L\'adresse ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $adresseLogementAdresse = null;

    #[Assert\Length(max: 100, maxMessage: 'L\'adresse ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $adresseLogementAdresseDetailNumero = null;
    #[Assert\NotBlank(message: 'Merci de saisir un code postal.')]
    #[Assert\Regex(pattern: '/^[0-9]{5}$/', message: 'Le code postal doit être composé de 5 chiffres.')]
    private ?string $adresseLogementAdresseDetailCodePostal = null;
    #[Assert\NotBlank(message: 'Merci de saisir une ville.')]
    #[Assert\Length(max: 100, maxMessage: 'La ville ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $adresseLogementAdresseDetailCommune = null;
    #[Assert\NotBlank(message: 'Merci de saisir un code INSEE.')]
    #[Assert\Regex(pattern: '/^[0-9]{5}$/', message: 'Le code insee doit être composé de 5 chiffres.')]
    private ?string $adresseLogementAdresseDetailInsee = null;
    #[Assert\Length(max: 50, maxMessage: 'La latitude ne peut pas dépasser {{ limit }} caractères.')]
    #[Assert\Regex(
        pattern: '/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?)$/',
        message: 'La latitude doit être un nombre décimal.'
    )]
    private ?float $adresseLogementAdresseDetailGeolocLat = null;
    #[Assert\Length(max: 50, maxMessage: 'La longitude ne peut pas dépasser {{ limit }} caractères.')]
    #[Assert\Regex(
        pattern: '/^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?)$/',
        message: 'La longitude doit être un nombre décimal.'
    )]
    private ?float $adresseLogementAdresseDetailGeolocLng = null;
    private ?bool $adresseLogementAdresseDetailManual = null;
    #[Assert\Length(max: 3, maxMessage: 'L\'escalier ne doit pas dépasser {{ limit }} caractères')]
    private ?string $adresseLogementComplementAdresseEscalier = null;
    #[Assert\Length(max: 5, maxMessage: 'L\'étage ne doit pas dépasser {{ limit }} caractères')]
    private ?string $adresseLogementComplementAdresseEtage = null;
    #[Assert\Length(max: 5, maxMessage: 'Le numéro d\'appartement ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $adresseLogementComplementAdresseNumeroAppartement = null;
    #[Assert\Length(max: 255)]
    private ?string $adresseLogementComplementAdresseAutre = null;
    #[Assert\NotBlank(message: 'Le profil du déclarant n\'est pas défini.')]
    #[Assert\Choice(
        choices: ['logement_occupez', 'autre_logement'],
        message: 'Le champs "signalementConcerneProfil" est incorrect'
    )]
    private ?string $signalementConcerneProfil = null;
    #[Assert\Choice(
        choices: ['locataire', 'bailleur_occupant'],
        message: 'Le champs "signalementConcerneProfilDetailOccupant" est incorrect.'
    )]
    private ?string $signalementConcerneProfilDetailOccupant = null;

    #[Assert\Choice(
        choices: ['tiers_particulier', 'tiers_pro', 'bailleur', 'service_secours'],
        message: 'Le champs "signalementConcerneProfilDetailTiers" est incorrect.'
    )]
    private ?string $signalementConcerneProfilDetailTiers = null;

    #[Assert\Choice(
        choices: ['particulier', 'organisme_societe'],
        message: 'Le champs "signalementConcerneProfilDetailBailleurProprietaire" est incorrect.'
    )]
    private ?string $signalementConcerneProfilDetailBailleurProprietaire = null;

    #[Assert\Choice(
        choices: ['particulier', 'organisme_societe'],
        message: 'Le champs "signalementConcerneProfilDetailBailleurBailleur" est incorrect.'
    )]
    private ?string $signalementConcerneProfilDetailBailleurBailleur = null;
    #[Assert\Choice(
        choices: ['oui', 'non', 'ne-sais-pas'],
        message: 'Le champs "signalementConcerneLogementSocialServiceSecours" est incorrect.'
    )]
    private ?string $signalementConcerneLogementSocialServiceSecours = null;
    #[Assert\Choice(
        choices: ['oui', 'non'],
        message: 'Le champs "signalementConcerneLogementSocialAutreTiers" est incorrect.'
    )]
    private ?string $signalementConcerneLogementSocialAutreTiers = null;
    #[Assert\NotBlank(
        message: 'Merci de renseigner le nom de l\'organisme.',
        groups: ['POST_TIERS_PRO', 'POST_SERVICE_SECOURS']
    )]
    #[Assert\Length(max: 200, maxMessage: 'Le nom de la structure ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $vosCoordonneesTiersNomOrganisme = null;
    #[Assert\Choice(
        choices: ['proche', 'voisin', 'autre'],
        message: 'Le champs "vosCoordonneesTiersLien" est incorrect.'
    )]
    private ?string $vosCoordonneesTiersLien = null;
    #[Assert\NotBlank(
        message: 'Merci de renseigner votre nom.',
        groups: ['POST_TIERS_PARTICULIER', 'POST_TIERS_PRO', 'POST_BAILLEUR', 'POST_SERVICE_SECOURS']
    )]
    #[Assert\Length(max: 50, maxMessage: 'Votre nom en tant que tiers est limité à {{ limit }} caractères.')]
    private ?string $vosCoordonneesTiersNom = null;
    #[Assert\NotBlank(
        message: 'Merci de renseigner votre prénom.',
        groups: ['POST_TIERS_PARTICULIER', 'POST_TIERS_PRO', 'POST_BAILLEUR', 'POST_SERVICE_SECOURS']
    )]
    #[Assert\Length(max: 50, maxMessage: 'Votre prénom en tant que tiers est limité à {{ limit }} caractères.')]
    private ?string $vosCoordonneesTiersPrenom = null;
    #[Assert\NotBlank(
        message: 'Merci de renseigner votre adresse e-mail.',
        groups: ['POST_TIERS_PARTICULIER', 'POST_TIERS_PRO', 'POST_BAILLEUR', 'POST_SERVICE_SECOURS']
    )]
    #[Assert\Email(mode: Email::VALIDATION_MODE_STRICT)]
    #[Assert\Length(max: 255, maxMessage: 'Votre email en tant que tiers ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $vosCoordonneesTiersEmail = null;
    #[Assert\NotBlank(
        message: 'Merci de renseigner votre numéro de téléphone.',
        groups: ['POST_TIERS_PARTICULIER', 'POST_TIERS_PRO', 'POST_BAILLEUR', 'POST_SERVICE_SECOURS']
    )]
    #[AppAssert\TelephoneFormat]
    private ?string $vosCoordonneesTiersTel = null;
    #[AppAssert\TelephoneFormat]
    private ?string $vosCoordonneesTiersTelSecondaire = null;
    #[Assert\NotBlank(
        message: 'Merci de renseigner votre civilité.',
        groups: ['POST_LOCATAIRE', 'POST_BAILLEUR_OCCUPANT']
    )]
    #[Assert\Choice(
        choices: ['mr', 'mme'],
        message: 'Le champs "vosCoordonneesOccupantCivilite" est incorrect.'
    )]
    private ?string $vosCoordonneesOccupantCivilite = null;
    #[Assert\Length(max: 200, maxMessage: 'Le nom de la structure ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $vosCoordonneesOccupantNomOrganisme = null;
    #[Assert\NotBlank(
        message: 'Merci de renseigner votre nom.',
        groups: ['POST_LOCATAIRE', 'POST_BAILLEUR_OCCUPANT']
    )]
    #[Assert\Length(max: 50, maxMessage: 'Votre nom en tant qu\'occupant est limité à {{ limit }} caractères.')]
    private ?string $vosCoordonneesOccupantNom = null;
    #[Assert\NotBlank(
        message: 'Merci de renseigner votre prénom.',
        groups: ['POST_LOCATAIRE', 'POST_BAILLEUR_OCCUPANT']
    )]
    #[Assert\Length(max: 50, maxMessage: 'Votre prénom en tant qu\'occupant est limité à {{ limit }} caractères.')]
    private ?string $vosCoordonneesOccupantPrenom = null;
    #[Assert\NotBlank(
        message: 'Merci de renseigner votre adresse e-mail.',
        groups: ['POST_LOCATAIRE', 'POST_BAILLEUR_OCCUPANT']
    )]
    #[Assert\Email(mode: Email::VALIDATION_MODE_STRICT)]
    #[Assert\Length(max: 255, maxMessage: 'Votre email ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $vosCoordonneesOccupantEmail = null;
    #[Assert\NotBlank(
        message: 'Merci de renseigner votre numéro de téléphone.',
        groups: ['POST_LOCATAIRE', 'POST_BAILLEUR_OCCUPANT']
    )]
    #[AppAssert\TelephoneFormat]
    private ?string $vosCoordonneesOccupantTel = null;
    #[AppAssert\TelephoneFormat]
    private ?string $vosCoordonneesOccupantTelSecondaire = null;
    #[Assert\NotBlank(
        message: 'Merci de renseigner le nom de l\'occupant.',
        groups: ['PUT_BAILLEUR', 'PUT_TIERS_PARTICULIER', 'PUT_TIERS_PRO']
    )]
    #[Assert\Length(max: 50, maxMessage: 'Le nom de l\'occupant ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $coordonneesOccupantNom = null;
    #[Assert\NotBlank(
        message: 'Merci de renseigner le prénom de l\'occupant.',
        groups: ['PUT_BAILLEUR', 'PUT_TIERS_PARTICULIER', 'PUT_TIERS_PRO']
    )]
    #[Assert\Length(max: 50, maxMessage: 'Le prénom de l\'occupant ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $coordonneesOccupantPrenom = null;
    #[Assert\Email(mode: Email::VALIDATION_MODE_STRICT)]
    #[Assert\Length(max: 255, maxMessage: 'L\'email de l\'occupant ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $coordonneesOccupantEmail = null;
    #[AppAssert\TelephoneFormat]
    private ?string $coordonneesOccupantTel = null;
    #[AppAssert\TelephoneFormat]
    private ?string $coordonneesOccupantTelSecondaire = null;
    #[Assert\Length(max: 250, maxMessage: 'Le nom du bailleur ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $coordonneesBailleurNom = null;
    #[Assert\Length(max: 250, maxMessage: 'Le prénom du bailleur ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $coordonneesBailleurPrenom = null;
    #[Assert\Email(mode: Email::VALIDATION_MODE_STRICT)]
    private ?string $coordonneesBailleurEmail = null;
    #[AppAssert\TelephoneFormat]
    private ?string $coordonneesBailleurTel = null;
    #[AppAssert\TelephoneFormat]
    private ?string $coordonneesBailleurTelSecondaire = null;
    #[Assert\Length(max: 255, maxMessage: 'L\'adresse du bailleur ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $coordonneesBailleurAdresse = null;
    #[Assert\Length(max: 255, maxMessage: 'L\'adresse du bailleur ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $coordonneesBailleurAdresseDetailNumero = null;
    #[Assert\Regex(pattern: '/^[0-9]{5}$/', message: 'Le code postal du bailleur doit être composé de 5 chiffres.')]
    private ?string $coordonneesBailleurAdresseDetailCodePostal = null;
    #[Assert\Length(max: 255, maxMessage: 'La ville du bailleur ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $coordonneesBailleurAdresseDetailCommune = null;
    #[Assert\Choice(
        choices: ['batiment', 'logement', 'batiment_logement'],
        message: 'Le champs "zoneConcerneeZone" est incorrect.'
    )]
    private ?string $zoneConcerneeZone = null;
    #[Assert\Choice(
        choices: ['appartement', 'maison', 'autre'],
        message: 'Le champs "typeLogementNature" est incorrect.'
    )]
    private ?string $typeLogementNature = null;
    #[Assert\Length(
        max: 15,
        maxMessage: 'Le champs typeLogementNatureAutrePrecision ne doit pas dépasser {{ limit }} caractères.')
    ]
    private ?string $typeLogementNatureAutrePrecision = null;
    #[Assert\Choice(
        choices: ['oui', 'non'],
        message: 'Le champs "typeLogementRdc" est incorrect.'
    )]
    private ?string $typeLogementRdc = null;
    #[Assert\Choice(
        choices: ['oui', 'non'],
        message: 'Le champs "typeLogementDernierEtage" est incorrect.'
    )]
    private ?string $typeLogementDernierEtage = null;
    #[Assert\Choice(
        choices: ['oui', 'non'],
        message: 'Le champs "typeLogementSousSolSansFenetre" est incorrect.'
    )]
    private ?string $typeLogementSousSolSansFenetre = null;
    #[Assert\Choice(
        choices: ['oui', 'non'],
        message: 'Le champs "typeLogementSousCombleSansFenetre" est incorrect.'
    )]
    private ?string $typeLogementSousCombleSansFenetre = null;
    #[Assert\Choice(
        choices: ['piece_unique', 'plusieurs_pieces'],
        message: 'Le champs "compositionLogementPieceUnique" est incorrect.'
    )]
    private ?string $compositionLogementPieceUnique = null;
    #[Assert\NotBlank(
        message: 'Merci de définir la superficie.',
        groups: [
            'PUT_LOCATAIRE',
            'PUT_BAILLEUR_OCCUPANT',
        ]
    )]
    #[Assert\Positive(message: 'Merci de saisir une information numérique dans le champs superficie.')]
    #[Assert\Type(type: 'numeric', message: 'Le nombre de pièces à vivre doit être un nombre.')]
    #[Assert\Length(
        max: 10,
        maxMessage: 'La superficie ne doit pas dépasser {{ limit }} caractères.',
    )]
    private ?string $compositionLogementSuperficie = null;

    #[Assert\Choice(
        choices: ['oui', 'non', 'nsp'],
        message: 'Le champs "compositionLogementHauteur" est incorrect.'
    )]
    private ?string $compositionLogementHauteur = null;
    #[Assert\NotBlank(
        message: 'Merci de définir le nombre de pièces à vivre.',
        groups: [
            'PUT_LOCATAIRE',
            'PUT_BAILLEUR_OCCUPANT',
            'PUT_BAILLEUR',
            'PUT_TIERS_PARTICULIER',
            'PUT_TIERS_PRO',
            'PUT_SERVICE_SECOURS',
        ]
    )]
    #[Assert\Positive(message: 'Merci de saisir une information numérique dans le champs nombre de pièces à vivre.')]
    #[Assert\Type(type: 'numeric', message: 'Le nombre de pièces à vivre doit être un nombre.')]
    #[Assert\Length(
        max: 10,
        maxMessage: 'Le nombre de pièces à vivre ne doit pas dépasser {{ limit }} caractères.',
    )]
    private ?string $compositionLogementNbPieces = null;

    #[Assert\Positive(message: 'Le nombre de personnes doit être un nombre positif.')]
    #[Assert\Type(type: 'numeric', message: 'Le nombre de personnes doit être un nombre.')]
    #[Assert\Length(
        max: 10,
        maxMessage: 'Le nombre de personnes ne doit pas dépasser {{ limit }} caractères.',
    )]
    private ?string $compositionLogementNombrePersonnes = null;

    #[Assert\Choice(
        choices: ['oui', 'non'],
        message: 'Le champs "compositionLogementEnfants" est incorrect.'
    )]
    private ?string $compositionLogementEnfants = null;
    #[Assert\Choice(
        choices: ['oui', 'non', 'nsp'],
        message: 'Le champs "typeLogementCommoditesPieceAVivre9m" est incorrect.'
    )]
    private ?string $typeLogementCommoditesPieceAVivre9m = null;
    #[Assert\Choice(
        choices: ['oui', 'non'],
        message: 'Le champs "typeLogementCommoditesCuisine" est incorrect.'
    )]
    private ?string $typeLogementCommoditesCuisine = null;
    #[Assert\Choice(
        choices: ['oui', 'non'],
        message: 'Le champs "typeLogementCommoditesCuisineCollective" est incorrect.'
    )]
    private ?string $typeLogementCommoditesCuisineCollective = null;
    #[Assert\Choice(
        choices: ['oui', 'non'],
        message: 'Le champs "typeLogementCommoditesSalleDeBain" est incorrect.'
    )]
    private ?string $typeLogementCommoditesSalleDeBain = null;
    #[Assert\Choice(
        choices: ['oui', 'non'],
        message: 'Le champs "typeLogementCommoditesSalleDeBainCollective" est incorrect.'
    )]
    private ?string $typeLogementCommoditesSalleDeBainCollective = null;
    #[Assert\Choice(
        choices: ['oui', 'non'],
        message: 'Le champs "typeLogementCommoditesWc" est incorrect.'
    )]
    private ?string $typeLogementCommoditesWc = null;
    #[Assert\Choice(
        choices: ['oui', 'non'],
        message: 'Le champs "typeLogementCommoditesWcCollective" est incorrect.'
    )]
    private ?string $typeLogementCommoditesWcCollective = null;
    #[Assert\Choice(
        choices: ['oui', 'non'],
        message: 'Le champs "typeLogementCommoditesWcCuisine" est incorrect.'
    )]
    private ?string $typeLogementCommoditesWcCuisine = null;
    #[Assert\DateTime('Y-m-d')]
    private ?string $bailDpeDateEmmenagement = null;
    #[Assert\Choice(choices: ['oui', 'non', 'nsp'], message: 'Le champ "bailDpeBail" est incorrect.')]
    private ?string $bailDpeBail = null;
    private ?array $bailDpeBailUpload = null;

    #[Assert\Choice(choices: ['oui', 'non', 'nsp'], message: 'Le champ "bailDpeEtatDesLieux" est incorrect.')]
    private ?string $bailDpeEtatDesLieux = null;
    #[Assert\Choice(choices: ['oui', 'non', 'nsp'], message: 'Le champ "bailDpeDpe" est incorrect.')]
    private ?string $bailDpeDpe = null;
    private ?array $bailDpeDpeUpload = null;
    #[Assert\Choice(choices: ['oui', 'non'], message: 'Le champ "logementSocialDemandeRelogement" est incorrect.')]
    private ?string $logementSocialDemandeRelogement = null;
    #[Assert\Choice(choices: ['oui', 'non', 'nsp'], message: 'Le champ "logementSocialAllocation" est incorrect.')]
    private ?string $logementSocialAllocation = null;
    #[Assert\Choice(choices: ['caf', 'msa'], message: 'Le champ "logementSocialAllocationCaisse" est incorrect.')]
    private ?string $logementSocialAllocationCaisse = null;
    #[Assert\DateTime('Y-m-d')]
    private ?string $logementSocialDateNaissance = null;
    #[Assert\Length(max: 20, maxMessage: 'Le montant de l\'allocation ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $logementSocialMontantAllocation = null;
    #[Assert\Length(max: 50, maxMessage: 'Le numéro d\'allocataire ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $logementSocialNumeroAllocataire = null;
    #[Assert\Choice(
        choices: ['oui', 'non', 'nsp'],
        message: 'Le champ "travailleurSocialQuitteLogement" est incorrect.',
    )]
    private ?string $travailleurSocialQuitteLogement = null;

    #[Assert\When(
        expression: 'this.getTravailleurSocialQuitteLogement() == "oui"',
        constraints: [
            new Assert\NotBlank(message: 'Merci de préciser s\'il y a un préavis de départ.'),
        ],
    )]
    #[Assert\Choice(
        choices: ['oui', 'non', 'nsp'],
        message: 'Le champ "travailleurSocialPreavisDepart" est incorrect.',
    )]
    private ?string $travailleurSocialPreavisDepart = null;
    #[Assert\Choice(
        choices: ['oui', 'non', 'nsp'],
        message: 'Le champ "travailleurSocialAccompagnement" est incorrect.',
    )]
    private ?string $travailleurSocialAccompagnement = null;
    #[Assert\Length(max: 50)]
    private ?string $travailleurSocialAccompagnementDeclarant = null;

    #[Assert\Choice(
        choices: ['oui', 'non', 'nsp'],
        message: 'Le champ "infoProcedureBailleurPrevenu" est incorrect.',
    )]
    private ?string $infoProcedureBailleurPrevenu = null;
    #[Assert\Choice(
        choices: ['oui', 'non', 'pas_assurance_logement', 'nsp'],
        message: 'Le champ "infoProcedureAssuranceContactee" est incorrect.',
    )]
    private ?string $infoProcedureAssuranceContactee = null;
    #[Assert\Length(
        max: 255,
        maxMessage: 'La réponse de l\'assurance ne doit pas dépasser {{ limit }} caractères.',
    )]
    private ?string $infoProcedureReponseAssurance = null;
    #[Assert\Choice(
        choices: ['oui', 'non', 'nsp'],
        message: 'Le champ "infoProcedureDepartApresTravaux" est incorrect.',
    )]
    private ?string $infoProcedureDepartApresTravaux = null;
    private ?bool $utilisationServiceOkPrevenirBailleur = null;
    private ?bool $utilisationServiceOkVisite = null;
    private ?bool $utilisationServiceOkDemandeLogement = null;
    #[Assert\Choice(
        choices: ['oui', 'non'],
        message: 'Le champ "informationsComplementairesSituationOccupantsBeneficiaireRsa" est incorrect.',
    )]
    private ?string $informationsComplementairesSituationOccupantsBeneficiaireRsa = null;
    #[Assert\Choice(
        choices: ['oui', 'non'],
        message: 'Le champ "informationsComplementairesSituationOccupantsBeneficiaireFsl" est incorrect.',
    )]
    private ?string $informationsComplementairesSituationOccupantsBeneficiaireFsl = null;
    #[Assert\DateTime('Y-m-d')]
    private ?string $informationsComplementairesSituationOccupantsDateNaissance = null;
    #[Assert\Choice(
        choices: ['oui', 'non'],
        message: 'Le champ "informationsComplementairesSituationOccupantsDemandeRelogement" est incorrect.',
    )]
    private ?string $informationsComplementairesSituationOccupantsDemandeRelogement = null;
    #[Assert\DateTime('Y-m-d')]
    private ?string $informationsComplementairesSituationOccupantsDateEmmenagement = null;
    #[Assert\Choice(
        choices: ['oui', 'non'],
        message: 'Le champ "informationsComplementairesSituationOccupantsLoyersPayes" est incorrect.',
    )]
    private ?string $informationsComplementairesSituationOccupantsLoyersPayes = null;
    #[Assert\Choice(
        choices: ['oui', 'non'],
        message: 'Le champ "informationsComplementairesSituationBailleurBeneficiaireRsa" est incorrect.',
    )]
    private ?string $informationsComplementairesSituationBailleurBeneficiaireRsa = null;
    #[Assert\Choice(
        choices: ['oui', 'non'],
        message: 'Le champ "informationsComplementairesSituationBailleurBeneficiaireFsl" est incorrect.',
    )]
    private ?string $informationsComplementairesSituationBailleurBeneficiaireFsl = null;
    #[Assert\Length(max: 50, maxMessage: 'Le revenu fiscal ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $informationsComplementairesSituationBailleurRevenuFiscal = null;
    #[Assert\DateTime('Y-m-d')]
    private ?string $informationsComplementairesSituationBailleurDateNaissance = null;
    #[Assert\Length(max: 20, maxMessage: 'Le montant de l\'allocation ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $informationsComplementairesLogementMontantLoyer = null;
    #[Assert\Length(max: 5, maxMessage: 'L\'étage ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $informationsComplementairesLogementNombreEtages = null;
    #[Assert\DateTime('Y')]
    private ?string $informationsComplementairesLogementAnneeConstruction = null;
    private ?string $messageAdministration = null;
    private array $files = [];
    private ?array $categorieDisorders = null;

    public function getProfil(): ?string
    {
        return $this->profil;
    }

    public function setProfil(?string $profil): self
    {
        $this->profil = $profil;

        return $this;
    }

    public function getCurrentStep(): ?string
    {
        return $this->currentStep;
    }

    public function setCurrentStep(?string $currentStep): self
    {
        $this->currentStep = $currentStep;

        return $this;
    }

    public function getAdresseLogementAdresse(): ?string
    {
        return $this->adresseLogementAdresse;
    }

    public function setAdresseLogementAdresse(?string $adresseLogementAdresse): self
    {
        $this->adresseLogementAdresse = $adresseLogementAdresse;

        return $this;
    }

    public function getAdresseLogementAdresseDetailNumero(): ?string
    {
        return $this->adresseLogementAdresseDetailNumero;
    }

    public function setAdresseLogementAdresseDetailNumero(?string $adresseLogementAdresseDetailNumero): self
    {
        $this->adresseLogementAdresseDetailNumero = $adresseLogementAdresseDetailNumero;

        return $this;
    }

    public function getAdresseLogementAdresseDetailCodePostal(): ?string
    {
        return $this->adresseLogementAdresseDetailCodePostal;
    }

    public function setAdresseLogementAdresseDetailCodePostal(?string $adresseLogementAdresseDetailCodePostal): self
    {
        $this->adresseLogementAdresseDetailCodePostal = $adresseLogementAdresseDetailCodePostal;

        return $this;
    }

    public function getAdresseLogementAdresseDetailCommune(): ?string
    {
        return $this->adresseLogementAdresseDetailCommune;
    }

    public function setAdresseLogementAdresseDetailCommune(?string $adresseLogementAdresseDetailCommune): self
    {
        $this->adresseLogementAdresseDetailCommune = $adresseLogementAdresseDetailCommune;

        return $this;
    }

    public function getAdresseLogementAdresseDetailInsee(): ?string
    {
        return $this->adresseLogementAdresseDetailInsee;
    }

    public function setAdresseLogementAdresseDetailInsee(?string $adresseLogementAdresseDetailInsee): self
    {
        $this->adresseLogementAdresseDetailInsee = $adresseLogementAdresseDetailInsee;

        return $this;
    }

    public function getAdresseLogementAdresseDetailGeolocLat(): ?float
    {
        return $this->adresseLogementAdresseDetailGeolocLat;
    }

    public function setAdresseLogementAdresseDetailGeolocLat(?float $adresseLogementAdresseDetailGeolocLat): self
    {
        $this->adresseLogementAdresseDetailGeolocLat = $adresseLogementAdresseDetailGeolocLat;

        return $this;
    }

    public function getAdresseLogementAdresseDetailGeolocLng(): ?float
    {
        return $this->adresseLogementAdresseDetailGeolocLng;
    }

    public function setAdresseLogementAdresseDetailGeolocLng(?float $adresseLogementAdresseDetailGeolocLng): self
    {
        $this->adresseLogementAdresseDetailGeolocLng = $adresseLogementAdresseDetailGeolocLng;

        return $this;
    }

    public function getAdresseLogementAdresseDetailManual(): ?bool
    {
        return $this->adresseLogementAdresseDetailManual;
    }

    public function setAdresseLogementAdresseDetailManual(?bool $adresseLogementAdresseDetailManual): self
    {
        $this->adresseLogementAdresseDetailManual = $adresseLogementAdresseDetailManual;

        return $this;
    }

    public function getAdresseLogementComplementAdresseEscalier(): ?string
    {
        return $this->adresseLogementComplementAdresseEscalier;
    }

    public function setAdresseLogementComplementAdresseEscalier(?string $adresseLogementComplementAdresseEscalier): self
    {
        $this->adresseLogementComplementAdresseEscalier = $adresseLogementComplementAdresseEscalier;

        return $this;
    }

    public function getAdresseLogementComplementAdresseEtage(): ?string
    {
        return $this->adresseLogementComplementAdresseEtage;
    }

    public function setAdresseLogementComplementAdresseEtage(?string $adresseLogementComplementAdresseEtage): self
    {
        $this->adresseLogementComplementAdresseEtage = $adresseLogementComplementAdresseEtage;

        return $this;
    }

    public function getAdresseLogementComplementAdresseNumeroAppartement(): ?string
    {
        return $this->adresseLogementComplementAdresseNumeroAppartement;
    }

    public function setAdresseLogementComplementAdresseNumeroAppartement(
        ?string $adresseLogementComplementAdresseNumeroAppartement
    ): self {
        $this->adresseLogementComplementAdresseNumeroAppartement = $adresseLogementComplementAdresseNumeroAppartement;

        return $this;
    }

    public function getAdresseLogementComplementAdresseAutre(): ?string
    {
        return $this->adresseLogementComplementAdresseAutre;
    }

    public function setAdresseLogementComplementAdresseAutre(?string $adresseLogementComplementAdresseAutre): self
    {
        $this->adresseLogementComplementAdresseAutre = $adresseLogementComplementAdresseAutre;

        return $this;
    }

    public function getSignalementConcerneProfil(): ?string
    {
        return $this->signalementConcerneProfil;
    }

    public function setSignalementConcerneProfil(?string $signalementConcerneProfil): self
    {
        $this->signalementConcerneProfil = $signalementConcerneProfil;

        return $this;
    }

    public function getSignalementConcerneProfilDetailOccupant(): ?string
    {
        return $this->signalementConcerneProfilDetailOccupant;
    }

    public function setSignalementConcerneProfilDetailOccupant(?string $signalementConcerneProfilDetailOccupant): self
    {
        $this->signalementConcerneProfilDetailOccupant = $signalementConcerneProfilDetailOccupant;

        return $this;
    }

    public function getSignalementConcerneProfilDetailTiers(): ?string
    {
        return $this->signalementConcerneProfilDetailTiers;
    }

    public function setSignalementConcerneProfilDetailTiers(?string $signalementConcerneProfilDetailTiers): self
    {
        $this->signalementConcerneProfilDetailTiers = $signalementConcerneProfilDetailTiers;

        return $this;
    }

    public function getSignalementConcerneProfilDetailBailleurProprietaire(): ?string
    {
        return $this->signalementConcerneProfilDetailBailleurProprietaire;
    }

    public function setSignalementConcerneProfilDetailBailleurProprietaire(
        ?string $signalementConcerneProfilDetailBailleurProprietaire
    ): self {
        $this->signalementConcerneProfilDetailBailleurProprietaire
            = $signalementConcerneProfilDetailBailleurProprietaire;

        return $this;
    }

    public function getSignalementConcerneProfilDetailBailleurBailleur(): ?string
    {
        return $this->signalementConcerneProfilDetailBailleurBailleur;
    }

    public function setSignalementConcerneProfilDetailBailleurBailleur(
        ?string $signalementConcerneProfilDetailBailleurBailleur
    ): self {
        $this->signalementConcerneProfilDetailBailleurBailleur = $signalementConcerneProfilDetailBailleurBailleur;

        return $this;
    }

    public function getSignalementConcerneLogementSocialServiceSecours(): ?string
    {
        return $this->signalementConcerneLogementSocialServiceSecours;
    }

    public function setSignalementConcerneLogementSocialServiceSecours(
        ?string $signalementConcerneLogementSocialServiceSecours
    ): self {
        $this->signalementConcerneLogementSocialServiceSecours = $signalementConcerneLogementSocialServiceSecours;

        return $this;
    }

    public function getSignalementConcerneLogementSocialAutreTiers(): ?string
    {
        return $this->signalementConcerneLogementSocialAutreTiers;
    }

    public function setSignalementConcerneLogementSocialAutreTiers(
        ?string $signalementConcerneLogementSocialAutreTiers
    ): self {
        $this->signalementConcerneLogementSocialAutreTiers = $signalementConcerneLogementSocialAutreTiers;

        return $this;
    }

    public function getVosCoordonneesOccupantCivilite(): ?string
    {
        return $this->vosCoordonneesOccupantCivilite;
    }

    public function setVosCoordonneesOccupantCivilite(?string $vosCoordonneesOccupantCivilite): self
    {
        $this->vosCoordonneesOccupantCivilite = $vosCoordonneesOccupantCivilite;

        return $this;
    }

    public function getVosCoordonneesOccupantNomOrganisme(): ?string
    {
        return $this->vosCoordonneesOccupantNomOrganisme;
    }

    public function setVosCoordonneesOccupantNomOrganisme(?string $vosCoordonneesOccupantNomOrganisme): self
    {
        $this->vosCoordonneesOccupantNomOrganisme = $vosCoordonneesOccupantNomOrganisme;

        return $this;
    }

    public function getVosCoordonneesOccupantNom(): ?string
    {
        return $this->vosCoordonneesOccupantNom;
    }

    public function setVosCoordonneesOccupantNom(?string $vosCoordonneesOccupantNom): self
    {
        $this->vosCoordonneesOccupantNom = $vosCoordonneesOccupantNom;

        return $this;
    }

    public function getVosCoordonneesOccupantPrenom(): ?string
    {
        return $this->vosCoordonneesOccupantPrenom;
    }

    public function setVosCoordonneesOccupantPrenom(?string $vosCoordonneesOccupantPrenom): self
    {
        $this->vosCoordonneesOccupantPrenom = $vosCoordonneesOccupantPrenom;

        return $this;
    }

    public function getVosCoordonneesOccupantEmail(): ?string
    {
        return $this->vosCoordonneesOccupantEmail;
    }

    public function setVosCoordonneesOccupantEmail(?string $vosCoordonneesOccupantEmail): self
    {
        $this->vosCoordonneesOccupantEmail = $vosCoordonneesOccupantEmail;

        return $this;
    }

    public function getVosCoordonneesOccupantTel(): ?string
    {
        return $this->vosCoordonneesOccupantTel;
    }

    public function setVosCoordonneesOccupantTel(?string $vosCoordonneesOccupantTel): self
    {
        $this->vosCoordonneesOccupantTel = $vosCoordonneesOccupantTel;

        return $this;
    }

    public function getVosCoordonneesOccupantTelSecondaire(): ?string
    {
        return $this->vosCoordonneesOccupantTelSecondaire;
    }

    public function setVosCoordonneesOccupantTelSecondaire(?string $vosCoordonneesOccupantTelSecondaire): self
    {
        $this->vosCoordonneesOccupantTelSecondaire = $vosCoordonneesOccupantTelSecondaire;

        return $this;
    }

    public function getCoordonneesOccupantNom(): ?string
    {
        return $this->coordonneesOccupantNom;
    }

    public function setCoordonneesOccupantNom(?string $coordonneesOccupantNom): self
    {
        $this->coordonneesOccupantNom = $coordonneesOccupantNom;

        return $this;
    }

    public function getCoordonneesOccupantPrenom(): ?string
    {
        return $this->coordonneesOccupantPrenom;
    }

    public function setCoordonneesOccupantPrenom(?string $coordonneesOccupantPrenom): self
    {
        $this->coordonneesOccupantPrenom = $coordonneesOccupantPrenom;

        return $this;
    }

    public function getCoordonneesOccupantEmail(): ?string
    {
        return $this->coordonneesOccupantEmail;
    }

    public function setCoordonneesOccupantEmail(?string $coordonneesOccupantEmail): self
    {
        $this->coordonneesOccupantEmail = $coordonneesOccupantEmail;

        return $this;
    }

    public function getCoordonneesOccupantTel(): ?string
    {
        return $this->coordonneesOccupantTel;
    }

    public function setCoordonneesOccupantTel(?string $coordonneesOccupantTel): self
    {
        $this->coordonneesOccupantTel = $coordonneesOccupantTel;

        return $this;
    }

    public function getCoordonneesBailleurNom(): ?string
    {
        return $this->coordonneesBailleurNom;
    }

    public function setCoordonneesBailleurNom(?string $coordonneesBailleurNom): self
    {
        $this->coordonneesBailleurNom = $coordonneesBailleurNom;

        return $this;
    }

    public function getCoordonneesBailleurPrenom(): ?string
    {
        return $this->coordonneesBailleurPrenom;
    }

    public function setCoordonneesBailleurPrenom(?string $coordonneesBailleurPrenom): self
    {
        $this->coordonneesBailleurPrenom = $coordonneesBailleurPrenom;

        return $this;
    }

    public function getCoordonneesBailleurEmail(): ?string
    {
        return $this->coordonneesBailleurEmail;
    }

    public function setCoordonneesBailleurEmail(?string $coordonneesBailleurEmail): self
    {
        $this->coordonneesBailleurEmail = $coordonneesBailleurEmail;

        return $this;
    }

    public function getCoordonneesBailleurTel(): ?string
    {
        return $this->coordonneesBailleurTel;
    }

    public function setCoordonneesBailleurTel(?string $coordonneesBailleurTel): self
    {
        $this->coordonneesBailleurTel = $coordonneesBailleurTel;

        return $this;
    }

    public function getCoordonneesBailleurAdresse(): ?string
    {
        return $this->coordonneesBailleurAdresse;
    }

    public function setCoordonneesBailleurAdresse(?string $coordonneesBailleurAdresse): self
    {
        $this->coordonneesBailleurAdresse = $coordonneesBailleurAdresse;

        return $this;
    }

    public function getCoordonneesBailleurAdresseDetailNumero(): ?string
    {
        return $this->coordonneesBailleurAdresseDetailNumero;
    }

    public function setCoordonneesBailleurAdresseDetailNumero(?string $coordonneesBailleurAdresseDetailNumero): self
    {
        $this->coordonneesBailleurAdresseDetailNumero = $coordonneesBailleurAdresseDetailNumero;

        return $this;
    }

    public function getCoordonneesBailleurAdresseDetailCodePostal(): ?string
    {
        return $this->coordonneesBailleurAdresseDetailCodePostal;
    }

    public function setCoordonneesBailleurAdresseDetailCodePostal(
        ?string $coordonneesBailleurAdresseDetailCodePostal
    ): self {
        $this->coordonneesBailleurAdresseDetailCodePostal = $coordonneesBailleurAdresseDetailCodePostal;

        return $this;
    }

    public function getCoordonneesBailleurAdresseDetailCommune(): ?string
    {
        return $this->coordonneesBailleurAdresseDetailCommune;
    }

    public function setCoordonneesBailleurAdresseDetailCommune(?string $coordonneesBailleurAdresseDetailCommune): self
    {
        $this->coordonneesBailleurAdresseDetailCommune = $coordonneesBailleurAdresseDetailCommune;

        return $this;
    }

    public function getVosCoordonneesTiersNomOrganisme(): ?string
    {
        return $this->vosCoordonneesTiersNomOrganisme;
    }

    public function setVosCoordonneesTiersNomOrganisme(?string $vosCoordonneesTiersNomOrganisme): self
    {
        $this->vosCoordonneesTiersNomOrganisme = $vosCoordonneesTiersNomOrganisme;

        return $this;
    }

    public function getVosCoordonneesTiersLien(): ?string
    {
        return $this->vosCoordonneesTiersLien;
    }

    public function setVosCoordonneesTiersLien(?string $vosCoordonneesTiersLien): self
    {
        $this->vosCoordonneesTiersLien = $vosCoordonneesTiersLien;

        return $this;
    }

    public function getVosCoordonneesTiersNom(): ?string
    {
        return $this->vosCoordonneesTiersNom;
    }

    public function setVosCoordonneesTiersNom(?string $vosCoordonneesTiersNom): self
    {
        $this->vosCoordonneesTiersNom = $vosCoordonneesTiersNom;

        return $this;
    }

    public function getVosCoordonneesTiersEmail(): ?string
    {
        return $this->vosCoordonneesTiersEmail;
    }

    public function setVosCoordonneesTiersEmail(?string $vosCoordonneesTiersEmail): self
    {
        $this->vosCoordonneesTiersEmail = $vosCoordonneesTiersEmail;

        return $this;
    }

    public function getVosCoordonneesTiersTel(): ?string
    {
        return $this->vosCoordonneesTiersTel;
    }

    public function setVosCoordonneesTiersTel(?string $vosCoordonneesTiersTel): self
    {
        $this->vosCoordonneesTiersTel = $vosCoordonneesTiersTel;

        return $this;
    }

    public function getVosCoordonneesTiersPrenom(): ?string
    {
        return $this->vosCoordonneesTiersPrenom;
    }

    public function setVosCoordonneesTiersPrenom(?string $vosCoordonneesTiersPrenom): self
    {
        $this->vosCoordonneesTiersPrenom = $vosCoordonneesTiersPrenom;

        return $this;
    }

    public function getZoneConcerneeZone(): ?string
    {
        return $this->zoneConcerneeZone;
    }

    public function setZoneConcerneeZone(?string $zoneConcerneeZone): self
    {
        $this->zoneConcerneeZone = $zoneConcerneeZone;

        return $this;
    }

    public function getTypeLogementNature(): ?string
    {
        return $this->typeLogementNature;
    }

    public function setTypeLogementNature(?string $typeLogementNature): self
    {
        $this->typeLogementNature = $typeLogementNature;

        return $this;
    }

    public function getTypeLogementNatureAutrePrecision(): ?string
    {
        return $this->typeLogementNatureAutrePrecision;
    }

    public function setTypeLogementNatureAutrePrecision(?string $typeLogementNatureAutrePrecision): self
    {
        $this->typeLogementNatureAutrePrecision = $typeLogementNatureAutrePrecision;

        return $this;
    }

    public function getTypeLogementRdc(): ?string
    {
        return $this->typeLogementRdc;
    }

    public function setTypeLogementRdc(?string $typeLogementRdc): self
    {
        $this->typeLogementRdc = $typeLogementRdc;

        return $this;
    }

    public function getTypeLogementDernierEtage(): ?string
    {
        return $this->typeLogementDernierEtage;
    }

    public function setTypeLogementDernierEtage(?string $typeLogementDernierEtage): self
    {
        $this->typeLogementDernierEtage = $typeLogementDernierEtage;

        return $this;
    }

    public function getTypeLogementSousSolSansFenetre(): ?string
    {
        return $this->typeLogementSousSolSansFenetre;
    }

    public function setTypeLogementSousSolSansFenetre(?string $typeLogementSousSolSansFenetre): self
    {
        $this->typeLogementSousSolSansFenetre = $typeLogementSousSolSansFenetre;

        return $this;
    }

    public function getTypeLogementSousCombleSansFenetre(): ?string
    {
        return $this->typeLogementSousCombleSansFenetre;
    }

    public function setTypeLogementSousCombleSansFenetre(?string $typeLogementSousCombleSansFenetre): self
    {
        $this->typeLogementSousCombleSansFenetre = $typeLogementSousCombleSansFenetre;

        return $this;
    }

    public function getCompositionLogementPieceUnique(): ?string
    {
        return $this->compositionLogementPieceUnique;
    }

    public function setCompositionLogementPieceUnique(?string $compositionLogementPieceUnique): self
    {
        $this->compositionLogementPieceUnique = $compositionLogementPieceUnique;

        return $this;
    }

    public function getCompositionLogementSuperficie(): ?string
    {
        return $this->compositionLogementSuperficie;
    }

    public function setCompositionLogementSuperficie(?string $compositionLogementSuperficie): self
    {
        $this->compositionLogementSuperficie = $compositionLogementSuperficie;

        return $this;
    }

    public function getCompositionLogementHauteur(): ?string
    {
        return $this->compositionLogementHauteur;
    }

    public function setCompositionLogementHauteur(?string $compositionLogementHauteur): self
    {
        $this->compositionLogementHauteur = $compositionLogementHauteur;

        return $this;
    }

    public function getCompositionLogementNbPieces(): ?string
    {
        return $this->compositionLogementNbPieces;
    }

    public function setCompositionLogementNbPieces(?string $compositionLogementNbPieces): self
    {
        $this->compositionLogementNbPieces = $compositionLogementNbPieces;

        return $this;
    }

    public function getCompositionLogementNombrePersonnes(): ?string
    {
        return $this->compositionLogementNombrePersonnes;
    }

    public function setCompositionLogementNombrePersonnes(?string $compositionLogementNombrePersonnes): self
    {
        $this->compositionLogementNombrePersonnes = $compositionLogementNombrePersonnes;

        return $this;
    }

    public function getCompositionLogementEnfants(): ?string
    {
        return $this->compositionLogementEnfants;
    }

    public function setCompositionLogementEnfants(?string $compositionLogementEnfants): self
    {
        $this->compositionLogementEnfants = $compositionLogementEnfants;

        return $this;
    }

    public function getTypeLogementCommoditesPieceAVivre9m(): ?string
    {
        return $this->typeLogementCommoditesPieceAVivre9m;
    }

    public function setTypeLogementCommoditesPieceAVivre9m(?string $typeLogementCommoditesPieceAVivre9m): self
    {
        $this->typeLogementCommoditesPieceAVivre9m = $typeLogementCommoditesPieceAVivre9m;

        return $this;
    }

    public function getTypeLogementCommoditesCuisine(): ?string
    {
        return $this->typeLogementCommoditesCuisine;
    }

    public function setTypeLogementCommoditesCuisine(?string $typeLogementCommoditesCuisine): self
    {
        $this->typeLogementCommoditesCuisine = $typeLogementCommoditesCuisine;

        return $this;
    }

    public function getTypeLogementCommoditesCuisineCollective(): ?string
    {
        return $this->typeLogementCommoditesCuisineCollective;
    }

    public function setTypeLogementCommoditesCuisineCollective(?string $typeLogementCommoditesCuisineCollective): self
    {
        $this->typeLogementCommoditesCuisineCollective = $typeLogementCommoditesCuisineCollective;

        return $this;
    }

    public function getTypeLogementCommoditesSalleDeBain(): ?string
    {
        return $this->typeLogementCommoditesSalleDeBain;
    }

    public function setTypeLogementCommoditesSalleDeBain(?string $typeLogementCommoditesSalleDeBain): self
    {
        $this->typeLogementCommoditesSalleDeBain = $typeLogementCommoditesSalleDeBain;

        return $this;
    }

    public function getTypeLogementCommoditesSalleDeBainCollective(): ?string
    {
        return $this->typeLogementCommoditesSalleDeBainCollective;
    }

    public function setTypeLogementCommoditesSalleDeBainCollective(
        ?string $typeLogementCommoditesSalleDeBainCollective
    ): self {
        $this->typeLogementCommoditesSalleDeBainCollective = $typeLogementCommoditesSalleDeBainCollective;

        return $this;
    }

    public function getTypeLogementCommoditesWc(): ?string
    {
        return $this->typeLogementCommoditesWc;
    }

    public function setTypeLogementCommoditesWc(?string $typeLogementCommoditesWc): self
    {
        $this->typeLogementCommoditesWc = $typeLogementCommoditesWc;

        return $this;
    }

    public function getTypeLogementCommoditesWcCollective(): ?string
    {
        return $this->typeLogementCommoditesWcCollective;
    }

    public function setTypeLogementCommoditesWcCollective(?string $typeLogementCommoditesWcCollective): self
    {
        $this->typeLogementCommoditesWcCollective = $typeLogementCommoditesWcCollective;

        return $this;
    }

    public function getTypeLogementCommoditesWcCuisine(): ?string
    {
        return $this->typeLogementCommoditesWcCuisine;
    }

    public function setTypeLogementCommoditesWcCuisine(?string $typeLogementCommoditesWcCuisine): self
    {
        $this->typeLogementCommoditesWcCuisine = $typeLogementCommoditesWcCuisine;

        return $this;
    }

    public function getBailDpeDateEmmenagement(): ?string
    {
        return $this->bailDpeDateEmmenagement;
    }

    public function setBailDpeDateEmmenagement(?string $bailDpeDateEmmenagement): self
    {
        $this->bailDpeDateEmmenagement = $bailDpeDateEmmenagement;

        return $this;
    }

    public function getBailDpeBail(): ?string
    {
        return $this->bailDpeBail;
    }

    public function setBailDpeBail(?string $bailDpeBail): self
    {
        $this->bailDpeBail = $bailDpeBail;

        return $this;
    }

    public function getBailDpeBailUpload(): ?array
    {
        return $this->bailDpeBailUpload;
    }

    public function setBailDpeBailUpload(?array $bailDpeBailUpload): self
    {
        $this->bailDpeBailUpload = $bailDpeBailUpload;

        return $this;
    }

    public function getBailDpeEtatDesLieux(): ?string
    {
        return $this->bailDpeEtatDesLieux;
    }

    public function setBailDpeEtatDesLieux(?string $bailDpeEtatDesLieux): self
    {
        $this->bailDpeEtatDesLieux = $bailDpeEtatDesLieux;

        return $this;
    }

    public function getBailDpeDpe(): ?string
    {
        return $this->bailDpeDpe;
    }

    public function setBailDpeDpe(?string $bailDpeDpe): self
    {
        $this->bailDpeDpe = $bailDpeDpe;

        return $this;
    }

    public function getBailDpeDpeUpload(): ?array
    {
        return $this->bailDpeDpeUpload;
    }

    public function setBailDpeDpeUpload(?array $bailDpeDpeUpload): self
    {
        $this->bailDpeDpeUpload = $bailDpeDpeUpload;

        return $this;
    }

    public function getLogementSocialDemandeRelogement(): ?string
    {
        return $this->logementSocialDemandeRelogement;
    }

    public function setLogementSocialDemandeRelogement(?string $logementSocialDemandeRelogement): self
    {
        $this->logementSocialDemandeRelogement = $logementSocialDemandeRelogement;

        return $this;
    }

    public function getLogementSocialAllocation(): ?string
    {
        return $this->logementSocialAllocation;
    }

    public function setLogementSocialAllocation(?string $logementSocialAllocation): self
    {
        $this->logementSocialAllocation = $logementSocialAllocation;

        return $this;
    }

    public function getLogementSocialAllocationCaisse(): ?string
    {
        return $this->logementSocialAllocationCaisse;
    }

    public function setLogementSocialAllocationCaisse(?string $logementSocialAllocationCaisse): self
    {
        $this->logementSocialAllocationCaisse = $logementSocialAllocationCaisse;

        return $this;
    }

    public function getLogementSocialDateNaissance(): ?string
    {
        return $this->logementSocialDateNaissance;
    }

    public function setLogementSocialDateNaissance(?string $logementSocialDateNaissance): self
    {
        $this->logementSocialDateNaissance = $logementSocialDateNaissance;

        return $this;
    }

    public function getLogementSocialMontantAllocation(): ?string
    {
        return $this->logementSocialMontantAllocation;
    }

    public function setLogementSocialMontantAllocation(?string $logementSocialMontantAllocation): self
    {
        $this->logementSocialMontantAllocation = $logementSocialMontantAllocation;

        return $this;
    }

    public function getLogementSocialNumeroAllocataire(): ?string
    {
        return $this->logementSocialNumeroAllocataire;
    }

    public function setLogementSocialNumeroAllocataire(?string $logementSocialNumeroAllocataire): self
    {
        $this->logementSocialNumeroAllocataire = $logementSocialNumeroAllocataire;

        return $this;
    }

    public function getTravailleurSocialQuitteLogement(): ?string
    {
        return $this->travailleurSocialQuitteLogement;
    }

    public function setTravailleurSocialQuitteLogement(?string $travailleurSocialQuitteLogement): self
    {
        $this->travailleurSocialQuitteLogement = $travailleurSocialQuitteLogement;

        return $this;
    }

    public function getTravailleurSocialPreavisDepart(): ?string
    {
        return $this->travailleurSocialPreavisDepart;
    }

    public function setTravailleurSocialPreavisDepart(?string $travailleurSocialPreavisDepart): self
    {
        $this->travailleurSocialPreavisDepart = $travailleurSocialPreavisDepart;

        return $this;
    }

    public function getTravailleurSocialAccompagnement(): ?string
    {
        return $this->travailleurSocialAccompagnement;
    }

    public function setTravailleurSocialAccompagnement(?string $travailleurSocialAccompagnement): self
    {
        $this->travailleurSocialAccompagnement = $travailleurSocialAccompagnement;

        return $this;
    }

    public function getTravailleurSocialAccompagnementDeclarant(): ?string
    {
        return $this->travailleurSocialAccompagnementDeclarant;
    }

    public function setTravailleurSocialAccompagnementDeclarant(?string $travailleurSocialAccompagnementDeclarant): self
    {
        $this->travailleurSocialAccompagnementDeclarant = $travailleurSocialAccompagnementDeclarant;

        return $this;
    }

    public function getInfoProcedureBailleurPrevenu(): ?string
    {
        return $this->infoProcedureBailleurPrevenu;
    }

    public function setInfoProcedureBailleurPrevenu(?string $infoProcedureBailleurPrevenu): self
    {
        $this->infoProcedureBailleurPrevenu = $infoProcedureBailleurPrevenu;

        return $this;
    }

    public function getInfoProcedureAssuranceContactee(): ?string
    {
        return $this->infoProcedureAssuranceContactee;
    }

    public function setInfoProcedureAssuranceContactee(?string $infoProcedureAssuranceContactee): self
    {
        $this->infoProcedureAssuranceContactee = $infoProcedureAssuranceContactee;

        return $this;
    }

    public function getInfoProcedureReponseAssurance(): ?string
    {
        return $this->infoProcedureReponseAssurance;
    }

    public function setInfoProcedureReponseAssurance(?string $infoProcedureReponseAssurance): self
    {
        $this->infoProcedureReponseAssurance = $infoProcedureReponseAssurance;

        return $this;
    }

    public function getInfoProcedureDepartApresTravaux(): ?string
    {
        return $this->infoProcedureDepartApresTravaux;
    }

    public function setInfoProcedureDepartApresTravaux(?string $infoProcedureDepartApresTravaux): self
    {
        $this->infoProcedureDepartApresTravaux = $infoProcedureDepartApresTravaux;

        return $this;
    }

    public function getUtilisationServiceOkPrevenirBailleur(): ?bool
    {
        return $this->utilisationServiceOkPrevenirBailleur;
    }

    public function setUtilisationServiceOkPrevenirBailleur(?bool $utilisationServiceOkPrevenirBailleur): self
    {
        $this->utilisationServiceOkPrevenirBailleur = $utilisationServiceOkPrevenirBailleur;

        return $this;
    }

    public function getUtilisationServiceOkVisite(): ?bool
    {
        return $this->utilisationServiceOkVisite;
    }

    public function setUtilisationServiceOkVisite(?bool $utilisationServiceOkVisite): self
    {
        $this->utilisationServiceOkVisite = $utilisationServiceOkVisite;

        return $this;
    }

    public function getUtilisationServiceOkDemandeLogement(): ?bool
    {
        return $this->utilisationServiceOkDemandeLogement;
    }

    public function setUtilisationServiceOkDemandeLogement(?bool $utilisationServiceOkDemandeLogement): self
    {
        $this->utilisationServiceOkDemandeLogement = $utilisationServiceOkDemandeLogement;

        return $this;
    }

    public function getInformationsComplementairesSituationOccupantsBeneficiaireRsa(): ?string
    {
        return $this->informationsComplementairesSituationOccupantsBeneficiaireRsa;
    }

    public function setInformationsComplementairesSituationOccupantsBeneficiaireRsa(?string $informationsComplementairesSituationOccupantsBeneficiaireRsa): self
    {
        $this->informationsComplementairesSituationOccupantsBeneficiaireRsa = $informationsComplementairesSituationOccupantsBeneficiaireRsa;

        return $this;
    }

    public function getInformationsComplementairesSituationOccupantsBeneficiaireFsl(): ?string
    {
        return $this->informationsComplementairesSituationOccupantsBeneficiaireFsl;
    }

    public function setInformationsComplementairesSituationOccupantsBeneficiaireFsl(?string $informationsComplementairesSituationOccupantsBeneficiaireFsl): self
    {
        $this->informationsComplementairesSituationOccupantsBeneficiaireFsl = $informationsComplementairesSituationOccupantsBeneficiaireFsl;

        return $this;
    }

    public function getInformationsComplementairesSituationOccupantsDateNaissance(): ?string
    {
        return $this->informationsComplementairesSituationOccupantsDateNaissance;
    }

    public function setInformationsComplementairesSituationOccupantsDateNaissance(?string $informationsComplementairesSituationOccupantsDateNaissance): self
    {
        $this->informationsComplementairesSituationOccupantsDateNaissance = $informationsComplementairesSituationOccupantsDateNaissance;

        return $this;
    }

    public function getInformationsComplementairesLogementMontantLoyer(): ?string
    {
        return $this->informationsComplementairesLogementMontantLoyer;
    }

    public function getInformationsComplementairesSituationOccupantsDemandeRelogement(): ?string
    {
        return $this->informationsComplementairesSituationOccupantsDemandeRelogement;
    }

    public function setInformationsComplementairesSituationOccupantsDemandeRelogement(?string $informationsComplementairesSituationOccupantsDemandeRelogement): self
    {
        $this->informationsComplementairesSituationOccupantsDemandeRelogement = $informationsComplementairesSituationOccupantsDemandeRelogement;

        return $this;
    }

    public function getInformationsComplementairesSituationOccupantsDateEmmenagement(): ?string
    {
        return $this->informationsComplementairesSituationOccupantsDateEmmenagement;
    }

    public function setInformationsComplementairesSituationOccupantsDateEmmenagement(?string $informationsComplementairesSituationOccupantsDateEmmenagement): self
    {
        $this->informationsComplementairesSituationOccupantsDateEmmenagement = $informationsComplementairesSituationOccupantsDateEmmenagement;

        return $this;
    }

    public function getInformationsComplementairesSituationOccupantsLoyersPayes(): ?string
    {
        return $this->informationsComplementairesSituationOccupantsLoyersPayes;
    }

    public function setInformationsComplementairesSituationOccupantsLoyersPayes(?string $informationsComplementairesSituationOccupantsLoyersPayes): self
    {
        $this->informationsComplementairesSituationOccupantsLoyersPayes = $informationsComplementairesSituationOccupantsLoyersPayes;

        return $this;
    }

    public function getInformationsComplementairesSituationBailleurBeneficiaireRsa(): ?string
    {
        return $this->informationsComplementairesSituationBailleurBeneficiaireRsa;
    }

    public function setInformationsComplementairesSituationBailleurBeneficiaireRsa(?string $informationsComplementairesSituationBailleurBeneficiaireRsa): self
    {
        $this->informationsComplementairesSituationBailleurBeneficiaireRsa = $informationsComplementairesSituationBailleurBeneficiaireRsa;

        return $this;
    }

    public function getInformationsComplementairesSituationBailleurBeneficiaireFsl(): ?string
    {
        return $this->informationsComplementairesSituationBailleurBeneficiaireFsl;
    }

    public function setInformationsComplementairesSituationBailleurBeneficiaireFsl(?string $informationsComplementairesSituationBailleurBeneficiaireFsl): self
    {
        $this->informationsComplementairesSituationBailleurBeneficiaireFsl = $informationsComplementairesSituationBailleurBeneficiaireFsl;

        return $this;
    }

    public function getInformationsComplementairesSituationBailleurRevenuFiscal(): ?string
    {
        return $this->informationsComplementairesSituationBailleurRevenuFiscal;
    }

    public function setInformationsComplementairesSituationBailleurRevenuFiscal(?string $informationsComplementairesSituationBailleurRevenuFiscal): self
    {
        $this->informationsComplementairesSituationBailleurRevenuFiscal = $informationsComplementairesSituationBailleurRevenuFiscal;

        return $this;
    }

    public function getInformationsComplementairesSituationBailleurDateNaissance(): ?string
    {
        return $this->informationsComplementairesSituationBailleurDateNaissance;
    }

    public function setInformationsComplementairesSituationBailleurDateNaissance(?string $informationsComplementairesSituationBailleurDateNaissance): self
    {
        $this->informationsComplementairesSituationBailleurDateNaissance = $informationsComplementairesSituationBailleurDateNaissance;

        return $this;
    }

    public function setInformationsComplementairesLogementMontantLoyer(?string $informationsComplementairesLogementMontantLoyer): self
    {
        $this->informationsComplementairesLogementMontantLoyer = $informationsComplementairesLogementMontantLoyer;

        return $this;
    }

    public function getInformationsComplementairesLogementNombreEtages(): ?string
    {
        return $this->informationsComplementairesLogementNombreEtages;
    }

    public function setInformationsComplementairesLogementNombreEtages(?string $informationsComplementairesLogementNombreEtages): self
    {
        $this->informationsComplementairesLogementNombreEtages = $informationsComplementairesLogementNombreEtages;

        return $this;
    }

    public function getInformationsComplementairesLogementAnneeConstruction(): ?string
    {
        return $this->informationsComplementairesLogementAnneeConstruction;
    }

    public function setInformationsComplementairesLogementAnneeConstruction(?string $informationsComplementairesLogementAnneeConstruction): self
    {
        $this->informationsComplementairesLogementAnneeConstruction = $informationsComplementairesLogementAnneeConstruction;

        return $this;
    }

    public function getVosCoordonneesTiersTelSecondaire(): ?string
    {
        return $this->vosCoordonneesTiersTelSecondaire;
    }

    public function setVosCoordonneesTiersTelSecondaire(?string $vosCoordonneesTiersTelSecondaire): self
    {
        $this->vosCoordonneesTiersTelSecondaire = $vosCoordonneesTiersTelSecondaire;

        return $this;
    }

    public function getCoordonneesOccupantTelSecondaire(): ?string
    {
        return $this->coordonneesOccupantTelSecondaire;
    }

    public function setCoordonneesOccupantTelSecondaire(?string $coordonneesOccupantTelSecondaire): self
    {
        $this->coordonneesOccupantTelSecondaire = $coordonneesOccupantTelSecondaire;

        return $this;
    }

    public function getCoordonneesBailleurTelSecondaire(): ?string
    {
        return $this->coordonneesBailleurTelSecondaire;
    }

    public function setCoordonneesBailleurTelSecondaire(?string $coordonneesBailleurTelSecondaire): self
    {
        $this->coordonneesBailleurTelSecondaire = $coordonneesBailleurTelSecondaire;

        return $this;
    }

    /**
     * Make Signalement::details not null after MEP.
     *
     * @see Signalement::$details
     */
    public function getMessageAdministration(): ?string
    {
        if (empty($this->messageAdministration)) {
            return 'N/C';
        }

        return $this->messageAdministration;
    }

    public function setMessageAdministration(?string $messageAdministration): self
    {
        $this->messageAdministration = $messageAdministration;

        return $this;
    }

    public function getFiles(): ?array
    {
        return $this->files;
    }

    public function setFiles(?array $files): self
    {
        $this->files = $files;

        return $this;
    }

    public function getCategorieDisorders(): ?array
    {
        return $this->categorieDisorders;
    }

    public function setCategorieDisorders(?array $categorieDisorders): self
    {
        $this->categorieDisorders = $categorieDisorders;

        return $this;
    }
}
