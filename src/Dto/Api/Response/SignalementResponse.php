<?php

namespace App\Dto\Api\Response;

use App\Dto\Api\Model\Adresse;
use App\Dto\Api\Model\File;
use App\Dto\Api\Model\Intervention;
use App\Dto\Api\Model\Personne;
use App\Dto\Api\Model\Suivi;
use App\Entity\Enum\DebutDesordres;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\MotifRefus;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\Qualification;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

class SignalementResponse
{
    #[OA\Property(
        description: 'Identifiant technique du signalement',
        format: 'uuid',
        example: '123e4567-e89b-12d3-a456-426614174000'
    )]
    public string $uuid;
    #[OA\Property(
        description: 'Identifiant fonctionnel du signalement, composé de l\'année de dépôt et d\'un compteur séparé par un tiret.',
        format: 'année-compteur',
        example: '2023-125'
    )]
    public string $reference;
    #[OA\Property(
        description: 'Date de dépot du signalement',
        format: 'date-time',
        example: '2025-01-05T14:30:15+00:00'
    )]
    public string $dateCreation;
    #[OA\Property(
        ref: new Model(type: Adresse::class),
        description: 'Informations détaillées sur l\'adresse de l\'occupant',
    )]
    public Adresse $adresse;
    #[OA\Property(
        description: "Le statut du signalement peut prendre l'une des valeurs suivantes : `en cours` (le signalement est actif), `fermé` (le signalement est terminé), `refusé` (le signalement a été refusé), ou `archivé` (le signalement a été archivé).",
        type: 'string',
        enum: ['en cours', 'fermé', 'refusé', 'archivé'],
        example: 'nouveau'
    )]
    public string $statut;

    #[OA\Property(
        description: 'Date à laquelle le signalement a été validé par un responsable territoire.<br>
        Exemple : `2025-01-05T15:30:15+00:00`',
        format: 'date-time',
        example: '2025-01-05T15:30:15+00:00'
    )]
    public ?string $dateValidation;

    #[OA\Property(
        description: 'Date à laquelle le signalement a été cloturé par un responsable territoire.<br>
        Exemple : `2025-01-05T15:30:15+00:00`',
        format: 'date-time',
        example: '2025-01-05T15:30:15+00:00'
    )]
    public ?string $dateCloture;
    #[OA\Property(
        description: 'Motif de clôture du signalement, précisant la raison pour laquelle il a été clôturé.',
        example: 'LOGEMENT_DECENT',
        nullable: true
    )]
    public ?MotifCloture $motifCloture;

    #[OA\Property(
        description: 'Motif du refus du signalement, précisant la raison pour laquelle il a été refusé.',
        example: 'HORS_COMPETENCE',
        nullable: true
    )]
    public ?MotifRefus $motifRefus;

    #[OA\Property(
        description: "Indique si l'usager a abandonné la procédure.
        <ul>
            <li>`true` : l'usager a demandé l'arrêt de la procédure</li>
            <li>`false` : l'usager souhaite poursuivre la procédure</li>
            <li>`null` : aucune action particulière de l'usager n'a été indiquée.</li>
        </ul>
        ",
        example: true,
        nullable: true
    )]
    public ?bool $abandonProcedureUsager;
    #[OA\Property(
        description: 'Type de déclarant ayant déposé le signalement.',
        example: 'LOCATAIRE',
    )]
    public ?ProfileDeclarant $typeDeclarant;
    #[OA\Property(
        description: 'Détail de la situation par le déclarant ainsi que des démarches déjà engagées et toutes les informations utiles au traitement du dossier.',
        example: "Le logement présente des infiltrations d'eau à plusieurs endroits, avec une forte humidité dans les murs."
    )]
    public ?string $description;

    #[OA\Property(
        description: 'Nature du logement concerné par le signalement.',
        enum: ['maison', 'appartement', 'autre'],
        example: 'appartement',
        nullable: true
    )]
    public ?string $natureLogement;

    #[OA\Property(
        description: 'Précision sur la nature du logement si natureLogement est `autre`.',
        example: 'caravane',
        nullable: true
    )]
    public ?string $precisionNatureLogement;
    #[OA\Property(
        description: 'Indique si le logement concerné est un logement social.',
        example: true,
        nullable: true
    )]
    public ?bool $logementSocial;

    #[OA\Property(
        description: 'Superficie du logement en mètres carrés.',
        format: 'float',
        example: 45.5,
        nullable: true
    )]
    public ?float $superficie;

    #[OA\Property(
        description: "Indique si le logement est constitué d'une pièce unique.
    - `true` : le logement est une pièce unique,
    - `false` : le logement est composé de plusieurs pièces,
    - `null` : information non précisée.",
        example: true,
        nullable: true
    )]
    public ?bool $pieceUnique;

    #[OA\Property(
        description: 'Nombre de pièces principales du logement.',
        format: 'int',
        example: '3',
        nullable: true
    )]
    public ?string $nbPieces;
    #[OA\Property(
        description: 'Année de construction du logement.',
        format: 'int',
        example: '1995',
        nullable: true
    )]
    public ?string $anneeConstruction;

    #[OA\Property(
        description: 'Indique si la construction du logement est antérieure à 1949.',
        example: true,
        nullable: true
    )]
    public ?bool $constructionAvant1949;
    #[OA\Property(
        description: 'Nombre d\'étages dans le logement.',
        format: 'int',
        example: '2',
        nullable: true
    )]
    public ?string $nbNiveaux;

    #[OA\Property(
        description: 'Indique si le logement est situé au rez-de-chaussée.',
        example: false,
        nullable: true
    )]
    public ?bool $rezDeChaussee;
    #[OA\Property(
        description: 'Indique si le logement est situé au dernier étage.',
        example: false,
        nullable: true
    )]
    public ?bool $dernierEtage;

    #[OA\Property(
        description: 'Indique si le logement est au sous-sol sans fenêtre.',
        example: false,
        nullable: true
    )]
    public ?bool $sousSolSansFenetre;

    #[OA\Property(
        description: 'Indique si le logement est au sous-sol sans fenêtre.',
        example: false,
        nullable: true
    )]
    public ?bool $sousCombleSansFenetre;
    #[OA\Property(
        description: 'Indique si la pièce à vivre fait plus de 9m².',
        example: false,
        nullable: true
    )]
    public ?bool $pieceAVivreSuperieureA9m;
    #[OA\Property(
        description: 'Indique si le logement dispose d\'une cuisine.',
        example: true,
        nullable: true
    )]
    public ?bool $cuisine;

    #[OA\Property(
        description: 'Indique si la cuisine est collective.',
        type: 'boolean',
        example: false,
        nullable: true
    )]
    public ?bool $cuisineCollective;

    #[OA\Property(
        description: 'Indique si le logement dispose d\'une salle de bain indépendante.',
        example: true,
        nullable: true
    )]
    public ?bool $salleDeBain;

    #[OA\Property(
        description: 'Indique si le l\'occupant dispose d\'une salle de bain collective.',
        example: true,
        nullable: true
    )]
    public ?bool $salleDeBainCollective;

    #[OA\Property(
        description: 'Indique si le logement dispose de toilettes.',
        example: false,
        nullable: true
    )]
    public ?bool $wc;

    #[OA\Property(
        description: 'Indique si les toilettes se situent dans la cuisine',
        example: false,
        nullable: true
    )]
    public ?bool $wcDansCuisine;

    #[OA\Property(
        description: 'Indique si les toilettes sont collectives.',
        example: false,
        nullable: true
    )]
    public ?bool $wcCollectif;

    #[OA\Property(
        description: 'Indique si la hauteur sous plafond est supérieure à 2 mètres.',
        example: true,
        nullable: true
    )]
    public ?bool $hauteurSuperieureA2metres;

    #[OA\Property(
        description: 'Indique si un diagnostic de performance énergétique (DPE) existe pour le logement.',
        example: true,
        nullable: true
    )]
    public ?bool $dpeExistant;

    #[OA\Property(
        description: 'Classe énergétique du logement selon le diagnostic de performance énergétique (DPE).
        <ul>
            <li>La valeur doit être une lettre entre `A` et `G` selon la nomenclature du DPE</li>
            <li>`null` si aucune information.</li>
        </ul>',
        example: 'C',
        nullable: true
    )]
    public ?string $dpeClasseEnergetique;

    #[OA\Property(
        description: 'La date d\'entrée du locataire dans le logement, au format (AAAA-MM-JJ).<br>
        Exemple : `2025-01-05`',
        format: 'date',
        example: '2023-01-15',
        nullable: true
    )]
    public ?string $dateEntreeLogement;

    #[OA\Property(
        description: 'Nombre d\'occupants habitant actuellement dans le logement.',
        example: 4,
        nullable: true
    )]
    public ?int $nbOccupantsLogement;
    #[OA\Property(
        description: 'Indique si des enfants habitent dans le logement.',
        example: true,
        nullable: true
    )]
    public ?bool $enfantsDansLogement;
    #[OA\Property(
        description: 'Nombre d\'enfants habitant actuellement dans le logement.',
        example: 2,
        nullable: true
    )]
    public ?int $nombreEnfantsDansLogement;

    #[OA\Property(
        description: 'Indique si l\'assurance du logement a été contactée.',
        example: true,
        nullable: true
    )]
    public ?bool $assuranceContactee;

    #[OA\Property(
        description: 'Réponse de l\'assurance à la demande du locataire.',
        example: 'Nous acceptons les conditions de la prestation.',
        nullable: true
    )]
    public ?string $reponseAssurance;

    #[OA\Property(
        description: 'Indique si le locataire souhaite quitter le logement.',
        example: false,
        nullable: true
    )]
    public ?bool $souhaiteQuitterLogement;

    #[OA\Property(
        description: 'Indique si l\'occupant souhaite quitter le logement après les travaux.',
        example: false,
        nullable: true
    )]
    public ?bool $souhaiteQuitterLogementApresTravaux;

    #[OA\Property(
        description: 'Indique si l\'occupant est suivi par un travailleur social.',
        example: false,
        nullable: true
    )]
    public ?bool $suiviParTravailleurSocial;

    #[OA\Property(
        description: "Indique si le propriétaire a été averti d'une situation concernant le logement.",
        type: 'boolean',
        example: true,
        nullable: true
    )]
    public ?bool $proprietaireAverti;

    #[OA\Property(
        description: 'Moyen utilisé par le locataire pour avertir le propriétaire.
        <ul>
            <li>Courrier `courrier`</li>
            <li>E-mail `email`</li>
            <li>Téléphone `telephone`</li>
            <li>SMS `sms`</li>
            <li>Autre `autre`</li>
        </ul>
        ',
        type: 'string',
        example: 'sms',
        nullable: true
    )]
    public ?string $moyenInformationProprietaire;

    #[OA\Property(
        description: "Date à laquelle le propriétaire a été informé d'une situation liée au logement.<br>
        - Exemple (2023-09-15)",
        format: 'date',
        example: '2023-09-15',
        nullable: true
    )]
    public ?string $dateInformationProprietaire;

    #[OA\Property(
        description: 'Réponse donnée par le propriétaire concernant une situation liée au logement.',
        example: 'Refus de faire les travaux',
        nullable: true
    )]
    public ?string $reponseProprietaire;

    #[OA\Property(
        description: 'Référence unique fournie par le bailleur public dans le cadre d\'une réclamation.',
        type: 'string',
        nullable: true
    )]
    public ?string $numeroReclamationProprietaire;

    #[OA\Property(
        description: 'Montant du loyer.',
        format: 'float',
        example: 750.50,
        nullable: true
    )]
    public ?float $loyer;

    #[OA\Property(
        description: 'Indique si un bail est actuellement en cours.',
        example: true,
        nullable: true
    )]
    public ?bool $bailEnCours;

    #[OA\Property(
        description: 'Indique si un bail est existe.',
        example: true,
        nullable: true
    )]
    public ?bool $bailExistant;

    #[OA\Property(
        description: 'Identifiant fiscal invariant attribué au logement.',
        type: 'string',
        example: '123456789ABC',
        nullable: true
    )]
    public ?string $invariantFiscal;

    #[OA\Property(
        description: 'Indique si un état des lieux a été réalisé et est disponible.',
        example: true,
        nullable: true
    )]
    public ?bool $etatDesLieuxExistant;

    #[OA\Property(
        description: 'Indique si le locataire a transmis un préavis de départ.',
        type: 'boolean',
        example: false,
        nullable: true
    )]
    public ?bool $preavisDepartTransmis;

    #[OA\Property(
        description: 'Indique si une demande de relogement a été effectuée.',
        type: 'boolean',
        example: true,
        nullable: true
    )]
    public ?bool $demandeRelogementEffectuee;

    #[OA\Property(
        description: 'Indique si les loyers du logement sont payés.',
        type: 'boolean',
        example: true,
        nullable: true
    )]
    public ?bool $loyersPayes;
    public ?string $dateEffetBail;
    #[OA\Property(
        description: "Liste des désordres associés au logement signalant les problèmes affectant la qualité, la sécurité ou les conditions d'habitabilité.<br>
        Les désordres sur le logement concernent tous les problèmes à l'intérieur du logement ou batiment, par exemple :<br>
        <ul>
            <li>Aération</li>
            <li>Chauffage</li>
            <li>Moisissure</li>
            <li>Équipements</li>
            <li>Électricité</li>
            <li>Nuisibles</li>
            <li>ect.</li>
        </ul>
        <code>
            [
                {
                    \"categorie\": \"Équipements\",
                    \"zone\": LOGEMENT,
                    \"details\": [
                        \"Les prises électriques ne fonctionnent pas.\",
                        \"Il n'y a pas d'éclairage dans le logement.\"
                    ]
                }
            ]
        </code>


        ",
        type: 'array',
        items: new OA\Items(properties: [
            new OA\Property(property: 'categorie', description: 'Catégorie principale du désordre.', type: 'string'),
            new OA\Property(property: 'zone', description: 'Zone exacte du logement où le désordre est constaté.', type: 'string', nullable: true),
            new OA\Property(property: 'details', description: 'Liste des observations détaillées associées au désordre.', type: 'array', items: new OA\Items(type: 'string')),
        ], type: 'object'),
        example: [
            [
                'categorie' => 'Équipements',
                'zone' => 'LOGEMENT',
                'details' => [
                    'Les prises électriques ne fonctionnent pas.',
                    "Il n'y a pas d'éclairage dans le logement.",
                ],
            ],
        ]
    )]
    public array $desordres = [];
    #[OA\Property(
        description: "Score calculé sur la base des informations fournies par l'utilisateur. <br>
        Ce score est utilisé pour évaluer et pré-qualifier le signalement, permettant ainsi de prioriser ou orienter son traitement.<br><br>
        **Le calcul des scores :**<br>
        <ul>
            <li>Tous les désordres *Bâtiments* sélectionnés sont additionnés, puis pondérés par le score *Bâtiment* maximum possible.</li>
            <li>Tous les désordres *Logements* sélectionnés sont additionnés, puis pondérés par le score *Logement* maximum possible.</li>
            <li>Enfin, ces deux scores sont additionnés et divisés par 2 pour définir le score moyen.</li>
        </ul>
        *A noter : la présence d'enfants de moins de 6 ans sur-pondère l'évaluation*
        ",
        format: 'float',
        example: 32.6,
        nullable: true
    )]
    public ?float $score;

    #[OA\Property(
        description: 'Score qui concerne la zone batiment',
        format: 'float',
        example: 32.6,
        nullable: true
    )]
    public ?float $scoreBatiment;
    #[OA\Property(
        description: 'Score qui concerne la zone logement',
        format: 'float',
        example: 32.6,
        nullable: true
    )]
    public ?float $scoreLogement;
    #[OA\Property(
        description: "Décrit depuis combien de temps les désordres ont commencé.
        Les valeurs disponibles sont :
        <ul>
            <li>Moins d'un mois</li>
            <li>Entre 1 mois et 6 mois</li>
            <li>Entre 6 mois et 1 an</li>
            <li>Entre 1 et 2 ans</li>
            <li>Plus de 2 ans</li>
            <li>Ne sait pas</li>
        </ul>",
        example: 'MONTHS_1_to_6',
        nullable: true
    )]
    public ?DebutDesordres $debutDesordres = null;

    #[OA\Property(
        description: 'Indique si des désordres ont été constatés',
        example: true,
        nullable: true
    )]
    public ?bool $desordresConstates = null;
    public array $tags = [];

    #[OA\Property(
        description: 'Liste des qualifications calculées en fonction du score et des procédures associées.<br>
        **Du score à la pré-qualification :**<br>
        <ul>
            <li>**Entre 0 et 10** : `NON DECENCE` et/ou `RSD` et/ou `ASSURANTIEL` seront affichés si ces procédures sont rattachées à au moins un des désordres sélectionnés.
        Si un des désordres est rattaché à `INSALUBRITE OBLIGATOIRE`, la qualification `INSALUBRITE` pourra également être affichée.</li>
            <li> **Entre 10 et 30** : `NON DECENCE` et/ou `RSD` seront affichés si ces procédures sont rattachées à au moins un des désordres sélectionnés, et `MANQUEMENT A LA SALUBRITE` sera ajouté.
        Si un des désordres est rattaché à `INSALUBRITE OBLIGATOIRE`, la qualification INSALUBRITE pourra être affichée à la place de `MANQUEMENT A LA SALUBRITE`.</li>
            <li>**Entre 30 et 50** : `NON DECENCE` et/ou `RSD` et `INSALUBRITE` seront affichés si ces procédures sont rattachées à au moins un des désordres sélectionnés.</li>
            <li>**Au-delà de 50** : `NON DECENCE` et/ou `RSD` et/ou `PERIL` et `INSALUBRITE` seront affichés si ces procédures sont rattachées à au moins un des désordres sélectionnés.</li>
        </ul>
        ',
        type: 'array',
        items: new OA\Items(ref: Qualification::class),
        example: [
            'NON_DECENCE_CHECK',
            'RSD_CHECK',
            'INSALUBRITE_MANQUEMENT_CHECK',
            'SUROCCUPATION_CHECK',
            'DANGER_CHECK',
        ]
    )]
    public array $qualifications = [];
    #[OA\Property(
        description: 'Liste des suivis associés au signalement. Chaque suivi comprend des informations concernant son ID, sa date de création, sa description, son statut public/privé, son type et son auteur.',
        type: 'array',
        items: new OA\Items(ref: new Model(type: Suivi::class)),
        example: [
            [
                'id' => 1,
                'dateCreation' => '2024-11-01T10:00:00+00:00',
                'description' => 'Premier suivi associé.',
                'public' => true,
                'type' => 2,
                'createdBy' => 'John Doe',
            ],
            [
                'id' => 2,
                'dateCreation' => '2024-11-02T12:30:00+00:00',
                'description' => 'Deuxième suivi, accès limité.',
                'public' => false,
                'type' => 1,
                'createdBy' => 'Jane Doe',
            ],
        ]
    )]
    public array $suivis = [];
    #[OA\Property(
        description: 'Liste des visites ou arrêtés',
        type: 'array',
        items: new OA\Items(ref: new Model(type: Intervention::class))
    )]
    public array $interventions = [];
    #[OA\Property(
        description: 'Liste des fichiers joints au signalement',
        type: 'array',
        items: new OA\Items(ref: File::class)
    )]
    public array $files = [];

    #[OA\Property(
        description: 'Liste des personnes associées (occupant, déclarant, propriétaire), contenant des informations personnelles, leurs liens avec l’occupant, ainsi que leurs coordonnées.',
        type: 'array',
        items: new OA\Items(ref: Personne::class)
    )]
    public array $personnes = [];
    #[OA\Property(
        description: 'Correspond au nom du département<br>
        Exemple : `Bouches-du-Rhône`
        ',
        type: 'string',
        example: 'Bouches-du-Rhône',
    )]
    public ?string $territoireNom;
    #[OA\Property(
        description: 'Correspond au code insee du département<br>
        Exemple : `13`
        ',
        type: 'string',
        example: '13',
    )]
    public ?string $territoireCode;
    #[OA\Property(
        description: 'Indique si le signalement a été importé depuis une source externe.',
        type: 'boolean',
        example: false
    )]
    public bool $signalementImporte;
}
