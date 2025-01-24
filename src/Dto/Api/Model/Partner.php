<?php

namespace App\Dto\Api\Model;

use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
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
        description: 'Le type du partenaire.',
        example: 'ADIL'
    )]
    public PartnerType $type;
    #[OA\Property(
        description: 'Liste des compétences associées au partenaire.',
        type: 'array',
        items: new OA\Items(
            type: Qualification::class,
            enum: [
                'ACCOMPAGNEMENT_JURIDIQUE',
                'ACCOMPAGNEMENT_SOCIAL',
                'ACCOMPAGNEMENT_TRAVAUX',
                'ARRETES',
                'ASSURANTIEL',
                'CONCILIATION',
                'CONSIGNATION_AL',
                'DALO',
                'DIOGENE',
                'FSL',
                'HEBERGEMENT_RELOGEMENT',
                'INSALUBRITE',
                'MISE_EN_SECURITE_PERIL',
                'NON_DECENCE',
                'NON_DECENCE_ENERGETIQUE',
                'NUISIBLES',
                'RSD',
                'VISITES',
                'DANGER',
                'SUROCCUPATION',
            ],
        ),
        example: ['VISITES']
    )]
    public array $competences = [];

    public function __construct(
        PartnerEntity $partner,
    ) {
        $this->nom = $partner->getNom();
        $this->type = $partner->getType();
        $this->competences = $partner->getCompetence() ?? [];
    }
}
