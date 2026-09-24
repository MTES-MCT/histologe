<?php

namespace App\Dto\Api\Response;

use App\Dto\Api\Model\Adresse;
use App\Dto\Api\Model\Affectation;
use App\Dto\Api\Model\Desordre;
use App\Entity\Enum\CreationSource;
use App\Entity\Enum\DebutDesordres;
use App\Entity\Enum\EtageType;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\MotifRefus;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementStatus;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

class SignalementSummaryResponse
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

    /**
     * @var Affectation[]
     */
    #[OA\Property(
        description: 'Liste des affectations.',
        type: 'array',
        items: new OA\Items(ref: new Model(type: Affectation::class)),
        example: [
            [
                'uuid' => 'e96325bf-139e-4793-a7b4-a4c713a0fbd9',
                'partenaireUuid' => '85401893-8d92-11f0-8aa8-f6901f1203f4',
                'partenaireNom' => 'Ville de Marseille',
                'partenaireType' => 'COMMUNE_SCHS',
                'statut' => 'FERME',
                'dateAffectation' => '2025-01-05T14:30:15+00:00',
                'dateAcceptation' => '2025-01-05T14:30:15+00:00',
                'motifCloture' => 'LOGEMENT_DECENT',
                'motifRefus' => 'HORS_COMPETENCE',
            ],
        ]
    )]
    public array $affectations = [];

    #[OA\Property(
        ref: new Model(type: Adresse::class),
        description: 'Informations détaillées sur l\'adresse de l\'occupant',
        type: 'object',
    )]
    public Adresse $adresse;
    #[OA\Property(
        description: 'Statut du signalement',
        example: 'CLOSED'
    )]
    public SignalementStatus $statut;

    #[OA\Property(
        description: 'Date à laquelle le signalement a été validé par un responsable territoire.<br>
        Exemple : `2025-01-05T15:30:15+00:00`',
        format: 'date-time',
        example: '2025-01-05T15:30:15+00:00'
    )]
    public ?string $dateValidation;

    #[OA\Property(
        description: 'Date à laquelle le signalement a été fermé par un responsable territoire.<br>
        Exemple : `2025-01-05T15:30:15+00:00`',
        format: 'date-time',
        example: '2025-01-05T15:30:15+00:00'
    )]
    public ?string $dateCloture;
    #[OA\Property(
        description: 'Motif de clôture du signalement, précisant la raison pour laquelle il a été fermé.',
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
        description: 'Autres occupants de l\'immeuble ayant rencontré des désordres.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>',
        example: true,
        nullable: true
    )]
    public bool|string|null $autresOccupantsDesordre;

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
        description: 'Indique l\'étage du logement.',
        example: 'RDC',
        nullable: true
    )]
    public ?EtageType $etage;
    #[OA\Property(
        description: 'Indique si le logement a des fenêtres.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
           <li>`nsp` pour "Je ne sais pas".</li>
        </ul>
        ',
        example: false,
        nullable: true
    )]
    public bool|string|null $avecFenetres;

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
        description: 'Autre situation de vulnérabilité à mentionner.',
        example: 'Personne âgée',
        nullable: true
    )]
    public ?string $autreSituationVulnerabilite;

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
        description: 'Nom de la structure d\'accompagnement.',
        example: 'Structure Sociale ABC',
        nullable: true
    )]
    public ?string $nomStructureAccompagnement;

    #[OA\Property(
        description: 'Nom du référent social accompagnant l\'occupant.',
        example: 'Dupont',
        nullable: true
    )]
    public ?string $nomReferentSocial;

    #[OA\Property(
        description: 'Prénom du référent social accompagnant l\'occupant.',
        example: 'Dominique',
        nullable: true
    )]
    public ?string $prenomReferentSocial;

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
        description: 'Indique si un logement est actuellement vacant.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
        </ul>',
        example: true,
        nullable: true
    )]
    public ?bool $logementVacant;

    #[OA\Property(
        description: 'Indique si un bail est actuellement en cours.<br>
        <ul>
           <li>`true` pour "oui"</li>
           <li>`false` pour "non"</li>
        </ul>',
        example: true,
        nullable: true
    )]
    public ?bool $bailEnCours;

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
    /** @var list<mixed> $desordres */
    #[OA\Property(
        description: "Liste des désordres associés au signalement.<br>
        *Exemple* :<br>
```json
[
    {
        \"identifiant\": \"desordres_batiment_nuisibles_autres\",
        \"libelle\": \"Il y a un autre type de nuisibles\",
        \"precisions\": [],
        \"precisionsLibres\": [
            {
                \"identifiant\": \"desordres_batiment_nuisibles_autres\",
                \"description\": \"Invasion de fourmis.\"
            }
        ]
    },
    {
        \"identifiant\": \"desordres_logement_humidite_salle_de_bain\",
        \"libelle\": \"Le logement est humide et a des traces de moisissures\",
        \"precisions\": {
            \"desordres_logement_humidite_salle_de_bain_details_machine_non\": \"Dans : La salle de bain, salle d'eau et / ou les toilettes - Machine à laver, sèche-linge ou lave vaisselle : non\",
            \"desordres_logement_humidite_salle_de_bain_details_moisissure_apres_nettoyage_oui\": \"Dans : La salle de bain, salle d'eau et / ou les toilettes - Moisissure revient après nettoyage : oui\",
            \"desordres_logement_humidite_salle_de_bain_details_fuite_non\": \"Dans : La salle de bain, salle d'eau et / ou les toilettes - Fuite ou dégat des eaux : non\"
        },
        \"precisionsLibres\": []
    }
]
```
        ",
        type: 'array',
        items: new OA\Items(ref: new Model(type: Desordre::class)),
    )]
    /** @var array<mixed> */
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

    /** @var list<mixed> $tags */
    #[OA\Property(
        description: "Les étiquettes permettent de caractériser ou organiser les signalements.<br>
        Exemple : `['Urgent', 'Commission du 12/09 ', 'Péril']`
        ",
        type: 'array',
        items: new OA\Items(type: 'string'),
        example: ['Urgent', 'Commission du 12/09 ', 'Péril']
    )]
    /** @var array<mixed> */
    public array $tags = [];

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
    #[OA\Property(
        description: 'Indique la source de création du signalement.',
        type: 'string',
        example: 'API',
        nullable: true
    )]
    public ?CreationSource $sourceCreationSignalement;
}
