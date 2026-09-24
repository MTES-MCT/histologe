<?php

namespace App\Dto\Api\Response;

use App\Dto\Api\Model\File;
use App\Dto\Api\Model\Personne;
use App\Dto\Api\Model\Suivi;
use App\Dto\Api\Model\Visite;
use App\Entity\Enum\Qualification;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

class SignalementResponse extends SignalementSummaryResponse
{
    /** @var list<mixed> $qualifications */
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
                Qualification::SALETE,
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
    /** @var array<mixed> */
    public array $qualifications = [];

    /** @var list<mixed> $suivis */
    #[OA\Property(
        description: 'Liste des suivis associés au signalement. Chaque suivi comprend des informations concernant son ID, sa date de création, sa description, son statut public/privé, son type et son auteur.',
        type: 'array',
        items: new OA\Items(ref: new Model(type: Suivi::class)),
        example: [
            [
                'dateCreation' => '2024-11-01T10:00:00+00:00',
                'description' => 'Premier suivi associé.',
                'public' => true,
                'createdBy' => 'John Doe',
            ],
            [
                'dateCreation' => '2024-11-02T12:30:00+00:00',
                'description' => 'Deuxième suivi, accès limité.',
                'public' => false,
                'createdBy' => 'Jane Doe',
            ],
        ]
    )]
    /** @var array<mixed> */
    public array $suivis = [];

    /** @var list<mixed> $visites */
    #[OA\Property(
        description: 'Liste des visites ou arrêtés du logement effectués dans le cadre du traitement du dossier.',
        type: 'array',
        items: new OA\Items(ref: new Model(type: Visite::class)),
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
    /** @var array<mixed> */
    public array $visites = [];
    /** @var list<mixed> $files */
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
    /** @var array<mixed> */
    public array $files = [];

    /** @var list<mixed> $personnes */
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
    /** @var array<mixed> */
    public array $personnes = [];
}
