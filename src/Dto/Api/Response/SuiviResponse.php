<?php

namespace App\Dto\Api\Response;

use App\Entity\Suivi;
use OpenApi\Attributes as OA;

class SuiviResponse
{
    #[OA\Property(
        description: 'Date de création du suivi.<br>Exemple : `2024-11-01T10:00:00+00:00`',
        type: 'string',
        format: 'date-time',
        example: '2024-11-01T10:00:00+00:00'
    )]
    public string $dateCreation;

    #[OA\Property(
        description: 'Description détaillée du suivi, peut contenir des balises HTML.',
        type: 'string',
        example: '<ul><li>lorem</li><li>ipsum</li></ul>'
    )]
    public string $description;

    #[OA\Property(
        description: 'Indique si le suivi est visible pour les usagers.',
        type: 'boolean',
        example: true
    )]
    public bool $public;

    public function __construct(Suivi $suivi)
    {
        $this->dateCreation = $suivi->getCreatedAt()->format(\DATE_ATOM);
        $this->description = $suivi->getDescription();
        $this->public = $suivi->getIsPublic();
    }
}
