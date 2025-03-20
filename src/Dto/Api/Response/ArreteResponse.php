<?php

namespace App\Dto\Api\Response;

use App\Entity\Intervention;
use App\Entity\Suivi;
use OpenApi\Attributes as OA;

class ArreteResponse
{
    #[OA\Property(
        description: 'Identifiant technique de l\'arrêté',
        format: 'uuid',
        example: '123e4567-e89b-12d3-a456-426614174000'
    )]
    public ?string $uuid = null;

    #[OA\Property(
        description: 'Description de l\'arrête.',
        type: 'string',
        example: 'L\'arrêté 2021-222-006 du 10/08/2019 dans le dossier de n°2021/DD04/00129. Type arrêté: Arrêté L.511-19 - Insalubrité'
    )]
    public ?string $description = null;

    public function __construct(Intervention $intervention, Suivi $suivi)
    {
        $this->uuid = $intervention->getUuid();
        $this->description = $suivi->getDescription();
    }
}
