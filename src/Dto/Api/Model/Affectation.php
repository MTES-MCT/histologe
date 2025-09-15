<?php

namespace App\Dto\Api\Model;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\MotifRefus;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Affectation',
    description: 'Représentation d\'une affectation.',
)]
class Affectation
{
    #[OA\Property(
        description: 'Identifiant unique de l\'affectation.',
        type: 'string',
        example: 'e96325bf-139e-4793-a7b4-a4c713a0fbd9',
    )]
    public ?string $uuid = null;

    #[OA\Property(
        description: 'Identifiant du partenaire.',
        example: '85401893-8d92-11f0-8aa8-f6901f1203f4'
    )]
    public ?string $partenaireUuid;

    #[OA\Property(
        description: 'Nom du partenaire.',
        example: 'Ville de Marseille'
    )]
    public ?string $partenaireNom;

    #[OA\Property(
        description: 'Le statut d\'affectation',
        example: 'FERME'
    )]
    public AffectationStatus $statut;

    #[OA\Property(
        description: 'Date d\'affectation du signalement au partenaire.<br>Exemple : `2025-01-05T15:30:15+00:00`',
        format: 'date-time',
        example: '2025-01-05T14:30:15+00:00'
    )]
    public ?string $dateAffectation = null;

    #[OA\Property(
        description: 'Date d\'acceptation du signalement par le partenaire.<br>Exemple : `2025-01-05T15:30:15+00:00`',
        format: 'date-time',
        example: '2025-01-05T14:30:15+00:00'
    )]
    public ?string $dateAcceptation = null;

    #[OA\Property(
        description: 'Motif de clôture de l\'affectation, précisant la raison pour laquelle il a été clôturé.',
        example: 'LOGEMENT_DECENT',
        nullable: true
    )]
    public ?MotifCloture $motifCloture = null;

    #[OA\Property(
        description: 'Motif du refus de l\'affectation, précisant la raison pour laquelle il a été refusé.',
        example: 'HORS_COMPETENCE',
        nullable: true
    )]
    public ?MotifRefus $motifRefus = null;
}
