<?php

namespace App\Dto\Api\Model;

use App\Entity\Suivi as SuiviEntity;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Suivi',
    description: 'Représentation d\'un suivi.'
)]
class Suivi
{
    #[OA\Property(
        description: 'Identifiant unique du suivi.',
        type: 'integer',
        example: 1
    )]
    public int $id;

    #[OA\Property(
        description: 'Date de création du suivi.<br>Exemple : `2024-11-01T10:00:00+00:00`',
        type: 'string',
        format: 'date-time',
        example: '2024-11-01T10:00:00+00:00'
    )]
    public string $dateCreation;
    #[OA\Property(
        description: 'Description détaillée du suivi.',
        type: 'string',
        example: 'Premier suivi associé.'
    )]
    public string $description;

    #[OA\Property(
        description: 'Indique si le suivi est visible pour les usagers.',
        type: 'boolean',
        example: true
    )]
    public bool $public;

    #[OA\Property(
        description: 'Auteur ayant créé le suivi.',
        type: 'string',
        example: 'John Doe'
    )]
    public string $createdBy;

    public function __construct(
        SuiviEntity $suivi,
    ) {
        $this->id = $suivi->getId();
        $this->dateCreation = $suivi->getCreatedAt()->format(\DATE_ATOM);
        $this->description = $suivi->getDescription(); // traitement de suppression du html ? comment gérer les bouton/doc qui sont présent en dur  dans le contenu ?
        $this->public = $suivi->getIsPublic();
        $this->createdBy = $suivi->getCreatedByLabel();
    }
}
