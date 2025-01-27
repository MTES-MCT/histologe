<?php

namespace App\Dto\Api\Response;

use App\Dto\Api\Model\Adresse;
use App\Dto\Api\Model\Desordre;
use App\Dto\Api\Model\File;
use App\Dto\Api\Model\Intervention;
use App\Dto\Api\Model\Personne;
use App\Dto\Api\Model\Suivi;
use App\Entity\Enum\AffectationNewStatus;
use App\Entity\Enum\DebutDesordres;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\MotifRefus;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\SignalementNewStatus;
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
        description: 'Identifiant métier du signalement, composé de l\'année de dépôt et d\'un compteur séparé par un tiret.',
        format: 'année-compteur',
        example: '2023-125'
    )]
    public string $reference;
    #[OA\Property(
        description: 'Date de dépot du signalement.<br>Exemple : `2025-01-05T15:30:15+00:00`',
        format: 'date-time',
        example: '2025-01-05T14:30:15+00:00'
    )]
    public string $dateCreation;
    #[OA\Property(
        description: 'Date d\'affectation du signalement au partenaire.<br>Exemple : `2025-01-05T15:30:15+00:00`',
        format: 'date-time',
        example: '2025-01-05T14:30:15+00:00'
    )]
    public string $dateAffectation;
    #[OA\Property(
        ref: new Model(type: Adresse::class),
        description: 'Informations détaillées sur l\'adresse de l\'occupant',
        type: 'object',
    )]
    public Adresse $adresse;
    #[OA\Property(
        description: 'Statut du signalement',
        example: 'FERME'
    )]
    public SignalementNewStatus $statut;

    #[OA\Property(
        description: 'Le statut d\'affectation',
        example: 'FERME'
    )]
    public AffectationNewStatus $statutAffectation;

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
        description: 'Détails de la situation par le déclarant ainsi que des démarches déjà engagées et toutes les informations utiles au traitement du dossier.',
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
        description: 'Indique si le logement concerné est un logement social.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>
        ',
        example: true,
        nullable: true
    )]
    public bool|string|null $logementSocial;

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
        description: 'Indique si le logement est situé au rez-de-chaussée.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>
        ',
        example: false,
        nullable: true
    )]
    public bool|string|null $rezDeChaussee;
    #[OA\Property(
        description: 'Indique si le logement est situé au dernier étage.<br>
        <ul>
            `true` pour "oui",
            `false` pour "non",
            `nsp` pour "Je ne sais pas".
        </ul>',
        example: false,
        nullable: true
    )]
    public bool|string|null $dernierEtage;

    #[OA\Property(
        description: 'Indique si le logement est au sous-sol sans fenêtre.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        example: false,
        nullable: true
    )]
    public bool|string|null $sousSolSansFenetre;

    #[OA\Property(
        description: 'Indique si le logement est sous les combles sans fenêtre.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        example: false,
        nullable: true
    )]
    public bool|string|null $sousCombleSansFenetre;
    #[OA\Property(
        description: 'Indique si la pièce à vivre fait plus de 9m².<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        example: false,
        nullable: true
    )]
    public bool|string|null $pieceAVivreSuperieureA9m;
    #[OA\Property(
        description: 'Indique si le logement dispose d\'une cuisine.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        example: true,
        nullable: true
    )]
    public bool|string|null $cuisine;

    #[OA\Property(
        description: 'Indique si la cuisine est collective.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        type: 'boolean',
        example: false,
        nullable: true
    )]
    public bool|string|null $cuisineCollective;

    #[OA\Property(
        description: 'Indique si le logement dispose d\'une salle de bain indépendante.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        example: true,
        nullable: true
    )]
    public bool|string|null $salleDeBain;

    #[OA\Property(
        description: 'Indique si le l\'occupant dispose d\'une salle de bain collective.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        example: true,
        nullable: true
    )]
    public bool|string|null $salleDeBainCollective;

    #[OA\Property(
        description: 'Indique si le logement dispose de toilettes.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        example: false,
        nullable: true
    )]
    public bool|string|null $wc;

    #[OA\Property(
        description: 'Indique si les toilettes se situent dans la cuisine.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        example: false,
        nullable: true
    )]
    public bool|string|null $wcDansCuisine;

    #[OA\Property(
        description: 'Indique si les toilettes sont collectives.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        example: false,
        nullable: true
    )]
    public bool|string|null $wcCollectif;

    #[OA\Property(
        description: 'Indique si la hauteur sous plafond est supérieure à 2 mètres.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        example: true,
        nullable: true
    )]
    public bool|string|null $hauteurSuperieureA2metres;

    #[OA\Property(
        description: 'Indique si un diagnostic de performance énergétique (DPE) existe pour le logement.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        example: true,
        nullable: true
    )]
    public bool|string|null $dpeExistant;

    #[OA\Property(
        description: 'Classe énergétique du logement selon le diagnostic de performance énergétique (DPE).
        <ul>
            <li>La valeur doit être une lettre entre `A` et `G` selon la nomenclature du DPE</li>
            <li>`nsp` pour Je ne sais pas</li>
            <li>`null` si aucune information.</li>
        </ul>',
        example: 'C',
        nullable: true
    )]
    public ?string $dpeClasseEnergetique;

    #[OA\Property(
        description: 'La date d\'entrée du locataire dans le logement.<br>
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
        description: 'Indique si des enfants de moins de 6 ans habitent dans le logement.',
        example: true,
        nullable: true
    )]
    public ?bool $enfantsDansLogementMoinsSixAns;
    #[OA\Property(
        description: 'Nombre d\'enfants habitant actuellement dans le logement.',
        example: 2,
        nullable: true
    )]
    public ?int $nbEnfantsDansLogement;

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
        description: 'Indique si le locataire souhaite quitter le logement.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        example: false,
        nullable: true
    )]
    public bool|string|null $souhaiteQuitterLogement;

    #[OA\Property(
        description: 'Indique si l\'occupant souhaite quitter le logement après les travaux.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        example: false,
        nullable: true
    )]
    public bool|string|null $souhaiteQuitterLogementApresTravaux;

    #[OA\Property(
        description: 'Indique si l\'occupant est suivi par un travailleur social.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        example: false,
        nullable: true
    )]
    public bool|string|null $suiviParTravailleurSocial;

    #[OA\Property(
        description: "Indique si le propriétaire a été averti d'une situation concernant le logement.",
        type: 'boolean',
        example: true,
        nullable: true
    )]
    public ?bool $proprietaireAverti;

    #[OA\Property(
        description: 'Moyen utilisé par le locataire pour avertir le propriétaire.',
        type: 'string',
        enum: ['courrier', 'email', 'telephone', 'sms', 'autre', 'nsp'],
        example: 'sms',
        nullable: true
    )]
    public ?string $moyenInformationProprietaire;

    #[OA\Property(
        description: "Date à laquelle le propriétaire a été informé d'une situation liée au logement.<br>
        - Exemple : `2023-09`",
        format: 'date',
        example: '2023-09',
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
        description: 'Indique si un bail est actuellement en cours.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        example: true,
        nullable: true
    )]
    public bool|string|null $bailEnCours;

    #[OA\Property(
        description: 'Indique si un bail existe.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        example: true,
        nullable: true
    )]
    public bool|string|null $bailExistant;

    #[OA\Property(
        description: 'Identifiant fiscal invariant attribué au logement.',
        type: 'string',
        example: '123456789ABC',
        nullable: true
    )]
    public ?string $invariantFiscal;

    #[OA\Property(
        description: 'Indique si un état des lieux a été réalisé et est disponible.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        example: true,
        nullable: true
    )]
    public bool|string|null $etatDesLieuxExistant;

    #[OA\Property(
        description: 'Indique si le locataire a transmis un préavis de départ.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        type: 'boolean',
        example: false,
        nullable: true
    )]
    public bool|string|null $preavisDepartTransmis;

    #[OA\Property(
        description: 'Indique si une demande de relogement a été effectuée.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        type: 'boolean',
        example: true,
        nullable: true
    )]
    public bool|string|null $demandeRelogementEffectuee;

    #[OA\Property(
        description: 'Indique si les loyers du logement sont payés.',
        type: 'boolean',
        example: true,
        nullable: true
    )]
    public ?bool $loyersPayes;

    #[OA\Property(
        description: 'La date de prise d’effet du bail.<br>
        Exemple : `2020-10-10`',
        format: 'date',
        example: '2020-10-10'
    )]
    public ?string $dateEffetBail;
    #[OA\Property(
        description: "Liste des désordres associés au logement signalant les problèmes affectant la qualité, la sécurité ou les conditions d'habitabilité.<br>
        Les désordres sur le logement concernent tous les problèmes à l'intérieur du logement ou batiment. <br>
        **Liste des catégories de désordre Bâtiment :**
        <ul>
            <li>`Usage / entretien`</li>
            <li>`Equipements collectifs` </li>
            <li>`Etanchéité / isolation`</li>
            <li>`Bâtiment risques particuliers`</li>
            <li>`Structure du bâti`</li>
            <li>`Environnement / éclairage`</li>
        </ul>
        **Liste des catégories de désordre Logement :**
        <ul>
            <li>`Structure / équipements`</li>
            <li>`Suroccupation`</li>
            <li>`Eau potable / assainissement`</li>
            <li>`Aération / humidité`</li>
            <li>`Sécurite risques particuliers`</li>
            <li>`Propreté / entretien`</li>
            <li>`Eclairage`</li>
        </ul>
        *Exemple* :<br>

```json
[
    {
        \"categorie\": \"Équipements\",
        \"zone\": \"LOGEMENT\",
        \"details\": [
            \"Les prises électriques ne fonctionnent pas.\",
            \"Il n'y a pas d'éclairage dans le logement.\"
        ]
    },
    {
        \"categorie\": \"Etanchéité / isolation\",
        \"zone\": \"BATIMENT\",
        \"details\": [
            \"Les murs ne sont pas ou peu isolés\",
            \"Il manque des portes ou des fenêtres...\",
            \"Le dernier étage n'est pas isolé,...\",
            \"De l'eau s'infiltre par le sol ou les murs\"
        ]
    }
]
```
        ",
        type: 'array',
        items: new OA\Items(ref: new Model(type: Desordre::class)),
        example: [
            [
                'categorie' => 'Équipements',
                'zone' => 'LOGEMENT',
                'details' => [
                    'Les prises électriques ne fonctionnent pas.',
                    "Il n'y a pas d'éclairage dans le logement.",
                ],
            ],
            [
                'categorie' => 'Etanchéité / isolation',
                'zone' => 'BATIMENT',
                'details' => [
                    'Les murs ne sont pas ou peu isolés',
                    'Il manque des portes ou des fenêtres, ou elles sont mal isolées.',
                    "Le dernier étage n'est pas isolé, le toit n'est pas étanche\n - Le logement est sous les combles",
                    "De l'eau s'infiltre par le sol ou les murs",
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
        description: 'Décrit depuis combien de temps les désordres ont commencé.',
        example: 'MONTHS_1_to_6',
        nullable: true
    )]
    public ?DebutDesordres $debutDesordres = null;

    #[OA\Property(
        description: 'Indique si des désordres ont été constatés par le déclarant.',
        example: true,
        nullable: true
    )]
    public ?bool $desordresConstates = null;

    #[OA\Property(
        description: "Les étiquettes permettent de caractériser ou organiser les signalements.<br>
        Exemple : `['Urgent', 'Commission du 12/09 ', 'Péril']`
        ",
        type: 'array',
        items: new OA\Items(type: 'string'),
        example: ['Urgent', 'Commission du 12/09 ', 'Péril']
    )]
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
        <p>La liste affiché n\'est pas exhaustive</p>
        ',
        type: 'array',
        items: new OA\Items(
            type: 'string',
            enum: [
                Qualification::RSD,
                Qualification::INSALUBRITE,
                Qualification::ACCOMPAGNEMENT_JURIDIQUE,
                Qualification::ACCOMPAGNEMENT_SOCIAL,
                Qualification::ACCOMPAGNEMENT_TRAVAUX,
                Qualification::VISITES,
                Qualification::NON_DECENCE,
                Qualification::NON_DECENCE_ENERGETIQUE,
                Qualification::ARRETES,
                Qualification::ASSURANTIEL,
                Qualification::CONCILIATION,
                Qualification::CONSIGNATION_AL,
                Qualification::DALO,
                Qualification::DIOGENE,
                Qualification::FSL,
                Qualification::HEBERGEMENT_RELOGEMENT,
                Qualification::MISE_EN_SECURITE_PERIL,
                Qualification::NUISIBLES,
                Qualification::DANGER,
                Qualification::SUROCCUPATION,
            ],
            example: 'NON_DECENCE'
        ),
        example: [
            'NON_DECENCE',
            'RSD',
            'INSALUBRITE_MANQUEMENT',
            'SUROCCUPATION',
            'DANGER',
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
                'createdBy' => 'John Doe',
            ],
            [
                'id' => 2,
                'dateCreation' => '2024-11-02T12:30:00+00:00',
                'description' => 'Deuxième suivi, accès limité.',
                'public' => false,
                'createdBy' => 'Jane Doe',
            ],
        ]
    )]
    public array $suivis = [];
    #[OA\Property(
        description: 'Liste des visites ou arrêtés du logement effectués dans le cadre du traitement du dossier.',
        type: 'array',
        items: new OA\Items(ref: new Model(type: Intervention::class)),
        example: [
            [
                'dateIntervention' => '2024-10-10T08:00:00+00:00',
                'type' => 'Visite',
                'statut' => 'DONE',
                'details' => '<p>lorem ipsum</p>',
                'partner' => [
                    'nom' => 'Partenaire 13-01',
                    'type' => 'Autre',
                    'competences' => [
                        'VISITES',
                    ],
                ],
                'conclusions' => [
                    'NON_DECENCE',
                    'RSD',
                    'INSALUBRITE',
                ],
                'occupantPresent' => true,
                'proprietairePresent' => false,
            ],
        ]
    )]
    public array $interventions = [];
    #[OA\Property(
        description: 'Liste des fichiers joints au signalement.',
        type: 'array',
        items: new OA\Items(ref: new Model(type: File::class)),
        example: [
            [
                'titre' => 'Capture d’écran du 2025-01-13 09-48-11.png',
                'documentType' => 'PHOTO_VISITE',
                'url' => 'https://histologe-staging.osc-fr1.scalingo.io/show/5ca99705-5ef6-11ef-ba0f-0242ac110034',
            ],
            [
                'titre' => '9c2fef07-f2a9-4505-914a-523cbfb911df.png',
                'documentType' => 'AUTRE',
                'url' => 'https://histologe-staging.osc-fr1.scalingo.io/show/5ca99705-5ef6-11ef-ba0f-0242ac110034',
            ],
        ]
    )]
    public array $files = [];

    #[OA\Property(
        description: 'Liste des personnes associées (occupant, déclarant, propriétaire), contenant des informations personnelles, leurs liens avec l’occupant, ainsi que leurs coordonnées.',
        type: 'array',
        items: new OA\Items(ref: new Model(type: Personne::class)),
        example: [
            [
                'personneType' => 'OCCUPANT',
                'structure' => null,
                'lienOccupant' => null,
                'precisionTypeSiBailleur' => null,
                'estTravailleurSocialPourOccupant' => null,
                'civilite' => 'mme',
                'nom' => 'DOE',
                'prenom' => 'Jane',
                'email' => 'jane.doe@gmail.com',
                'telephone' => '+33600000000',
                'telephoneSecondaire' => null,
                'dateNaissance' => '2020-10-10',
                'revenuFiscal' => null,
                'beneficiaireRsa' => '1',
                'beneficiaireFsl' => '1',
                'allocataire' => '1',
                'typeAllocataire' => 'CAF',
                'numAllocataire' => '255',
                'montantAllocation' => '250',
                'adresse' => null,
            ],
            [
                'personneType' => 'PROPRIETAIRE',
                'structure' => null,
                'lienOccupant' => null,
                'precisionTypeSiBailleur' => null,
                'estTravailleurSocialPourOccupant' => null,
                'civilite' => null,
                'nom' => 'DOE',
                'prenom' => 'John',
                'email' => 'john.doe@gmail.com',
                'telephone' => '+33611121314',
                'telephoneSecondaire' => null,
                'dateNaissance' => null,
                'revenuFiscal' => null,
                'beneficiaireRsa' => null,
                'beneficiaireFsl' => null,
                'allocataire' => null,
                'typeAllocataire' => null,
                'numAllocataire' => null,
                'montantAllocation' => null,
                'adresse' => [
                    'adresse' => '10 Rue du 14 Juillet',
                    'codePostal' => '59260',
                    'ville' => 'Lille',
                    'etage' => null,
                    'escalier' => null,
                    'numAppart' => null,
                    'codeInsee' => null,
                    'latitude' => null,
                    'longitude' => null,
                    'adresseAutre' => null,
                    'rnbId' => null,
                    'cleBanAdresse' => null,
                ],
            ],
        ]
    )]
    public array $personnes = [];
    #[OA\Property(
        description: 'Correspond au nom du département',
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
