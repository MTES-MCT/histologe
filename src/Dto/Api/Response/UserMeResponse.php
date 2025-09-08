<?php

namespace App\Dto\Api\Response;

use App\Dto\Api\Model\Partner;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;

#[OA\Schema(
    schema: 'UserMeResponse',
    description: 'Réponse contenant les informations de l\'utilisateur connecté et des partenaires autorisés'
)]
class UserMeResponse
{
    #[Groups(['user:me'])]
    #[OA\Property(
        description: 'E-mail de l\'utilisateur api',
        format: 'string',
        example: 'email@example.com'
    )]
    public string $email;

    /** @var Partner[] */
    #[Groups(['user:me'])]
    #[OA\Property(
        description: 'Liste des partenaires autorisés pour l’utilisateur authentifié',
        type: 'array',
        items: new OA\Items(ref: '#/components/schemas/Partner')
    )]
    public array $partenairesAutorises;

    /**
     * @param Partner[] $partners
     */
    public function __construct(string $email, array $partners)
    {
        $this->email = $email;
        $this->partenairesAutorises = $partners;
    }
}
