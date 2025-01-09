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
        description: 'Le type du partenaire.',
        enum: [
            'ADIL',
            'ARS',
            'ASSOCIATION',
            'BAILLEUR_SOCIAL',
            'CAF_MSA',
            'CCAS',
            'COMMUNE_SCHS',
            'CONCILIATEURS',
            'CONSEIL_DEPARTEMENTAL',
            'DDETS',
            'DDT_M',
            'DISPOSITIF_RENOVATION_HABITAT',
            'EPCI',
            'OPERATEUR_VISITES_ET_TRAVAUX',
            'POLICE_GENDARMERIE',
            'PREFECTURE',
            'TRIBUNAL',
            'AUTRE',
        ],
        example: 'ADIL'
    )]
    public ?string $type;
    #[OA\Property(
        description: 'Liste des compétences associées au partenaire.<br>
        <ul>
            <li>`ACCOMPAGNEMENT_JURIDIQUE`</li>
            <li>`ACCOMPAGNEMENT_SOCIAL`</li>
            <li>`ACCOMPAGNEMENT_TRAVAUX`</li>
            <li>`ARRETES`</li>
            <li>`ASSURANTIEL`</li>
            <li>`CONCILIATION`</li>
            <li>`CONSIGNATION_AL`</li>
            <li>`DALO`</li>
            <li>`DIOGENE`</li>
            <li>`FSL`</li>
            <li>`HEBERGEMENT_RELOGEMENT`</li>
            <li>`INSALUBRITE`</li>
            <li>`MISE_EN_SECURITE_PERIL`</li>
            <li>`NON_DECENCE`</li>
            <li>`NON_DECENCE_ENERGETIQUE`</li>
            <li>`NUISIBLES`</li>
            <li>`RSD`</li>
            <li>`VISITES`</li>
            <li>`DANGER`</li>
            <li>`SUROCCUPATION`</li>
        </ul>
',
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
