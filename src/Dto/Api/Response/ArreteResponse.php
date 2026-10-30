<?php

namespace App\Dto\Api\Response;

use App\Entity\Arrete;
use App\Service\Arrete\ArreteDescriptionGenerator;
use OpenApi\Attributes as OA;

class ArreteResponse
{
    #[OA\Property(
        description: 'Description de l\'arrête.',
        type: 'string',
        example: 'L\'arrêté 2021-222-006 du 10/08/2019 dans le dossier de n°2021/DD04/00129. Type arrêté : Arrêté L.511-19 - Insalubrité'
    )]
    public ?string $description = null;

    public function __construct(Arrete $arrete)
    {
        $this->description = ArreteDescriptionGenerator::buildDescriptionArreteCreated($arrete);
    }
}
