<?php

namespace App\Dto\Api\Model;

use App\Entity\Partner as PartnerEntity;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Partner',
    description: 'Représentation d\'un partenaire.'
)]
class Partner
{
    #[OA\Property(
        description: 'Le nom du partenaire.',
        example: 'ADIL 13'
    )]
    public string $nom;

    #[OA\Property(
        description: 'Le nom du partenaire.',
        example: 'ADIL'
    )]
    public ?string $type;
    #[OA\Property(
        description: 'Liste des compétences associées au partenaire.',
        type: 'array',
        items: new OA\Items(type: 'string'),
        example: ['VISITES']
    )]
    public array $competences = [];

    public function __construct(
        PartnerEntity $partner,
    ) {
        $this->nom = $partner->getNom();
        $this->type = $partner->getType()?->label();
        $this->competences = $partner->getCompetence() ?? [];
    }
}
