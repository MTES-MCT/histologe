<?php

namespace App\Dto\Api\Model;

use App\Entity\Enum\InterventionType;
use App\Entity\Intervention as InterventionEntity;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Intervention',
    description: 'Représentation d\'une intervention.'
)]
class Intervention
{
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

    public function __construct(
        InterventionEntity $intervention,
    ) {
        $this->dateIntervention = $intervention->getScheduledAt()->format(\DATE_ATOM);
        $this->type = $intervention->getType();
        $this->statut = $intervention->getStatus();
        $this->partner = $intervention->getPartner() ? new Partner($intervention->getPartner()) : null;
        $this->details = $intervention->getDetails(); // traitement de suppression du html
        $this->conclusions = $intervention->getConcludeProcedure() ?? [];
        $this->occupantPresent = $intervention->isOccupantPresent();
        $this->proprietairePresent = $intervention->isProprietairePresent();
    }
}
