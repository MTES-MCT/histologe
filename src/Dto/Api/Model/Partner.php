<?php

namespace App\Dto\Api\Model;

use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Partner as PartnerEntity;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;

#[OA\Schema(
    schema: 'Partner',
    description: 'Représentation d\'un partenaire.'
)]
class Partner
{
    #[OA\Property(
        description: 'L\'identifiant du partenaire.',
        example: '4567'
    )]
    #[Groups(['user:me'])]
    public int $id;

    #[OA\Property(
        description: 'Le code département du partenaire.',
        example: '13'
    )]
    #[Groups(['user:me'])]
    public string $codeDepartement;

    #[OA\Property(
        description: 'Le nom du partenaire.',
        example: 'ADIL 13'
    )]
    #[Groups(['user:me'])]
    public string $nom;

    #[OA\Property(
        description: 'Le type du partenaire.',
        example: 'ADIL'
    )]
    #[Groups(['user:me'])]
    public PartnerType $type;

    /** @var array<Qualification> $competences */
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
        $this->id = $partner->getId();
        $this->nom = $partner->getNom();
        $this->codeDepartement = $partner->getTerritory()->getZip();
        $this->type = $partner->getType();
        $this->competences = $partner->getCompetence() ?? [];
    }
}
