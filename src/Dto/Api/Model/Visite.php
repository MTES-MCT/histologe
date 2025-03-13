<?php

namespace App\Dto\Api\Model;

use App\Entity\Enum\DocumentType;
use App\Entity\Enum\InterventionType;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Visite',
    description: 'Représentation d\'une visite.'
)]
class Visite
{
    #[OA\Property(
        description: 'Identifiant unique d\'une visite.',
        type: 'string',
        example: 'e96325bf-139e-4793-a7b4-a4c713a0fbd9',
    )]
    public ?string $uuid = null;
    #[OA\Property(
        description: 'Date de l\'intervention.',
        type: 'string',
        format: 'date-time',
        example: '2024-11-03T14:30:00+00:00',
        nullable: true
    )]
    public string $dateIntervention;

    #[OA\Property(
        description: 'Type d\'intervention réalisée.',
        example: 'VISITE',
        nullable: true
    )]
    public ?InterventionType $type;
    #[OA\Property(
        description: 'Statut de l\'intervention.',
        type: 'string',
        enum: ['PLANNED', 'DONE', 'NOT_DONE', 'CANCELED'],
        example: 'DONE',
        nullable: true
    )]
    public ?string $statut;

    #[OA\Property(
        ref: new Model(type: Partner::class),
        description: 'Partenaire ayant effectué l\'intervention.',
        type: 'object',
        nullable: true
    )]
    public ?Partner $partner;

    #[OA\Property(
        description: 'Détails additionnels relatifs à l\'intervention.',
        type: 'string',
        example: 'Travaux à prévoir.',
        nullable: true
    )]
    public ?string $details;

    #[OA\Property(
        description: 'Conclusions ou observations spécifiques liées à l\'intervention.',
        type: 'array',
        items: new OA\Items(type: 'string'),
        example: []
    )]
    public array $conclusions = [];

    #[OA\Property(
        description: 'Indique si l\'occupant était présent lors de l\'intervention.',
        type: 'boolean',
        example: true,
        nullable: true
    )]
    public ?bool $occupantPresent;

    #[OA\Property(
        description: 'Indique si le propriétaire était présent lors de l\'intervention.',
        type: 'boolean',
        example: false,
        nullable: true
    )]
    public ?bool $proprietairePresent;

    #[OA\Property(
        description: 'Liste des fichiers joints au signalement.',
        type: 'array',
        items: new OA\Items(ref: new Model(type: File::class)),
        example: [
            [
                'titre' => 'photo_visite-2025-01-13-09-48-11.png',
                'documentType' => DocumentType::PHOTO_VISITE,
                'url' => 'https://histologe-staging.osc-fr1.scalingo.io/show/5ca99705-5ef6-11ef-ba0f-0242ac110034',
            ],
            [
                'titre' => 'rapport_visite-2025-01-13-09-48-11.png',
                'documentType' => DocumentType::PROCEDURE_RAPPORT_DE_VISITE,
                'url' => 'https://histologe-staging.osc-fr1.scalingo.io/show/5ca99705-5ef6-11ef-ba0f-0242ac110034',
            ],
        ]
    )]
    public array $files = [];
}
