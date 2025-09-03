<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\Api\Model\Partner;
use App\Dto\Api\Response\UserMeResponse;
use App\Entity\User;
use App\Service\Security\PartnerAuthorizedResolver;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class UserGetMeController extends AbstractController
{
    public function __construct(private readonly PartnerAuthorizedResolver $partnerAuthorizedResolver)
    {
    }

    #[Route('/users/me', name: 'api_user_show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/users/me',
        description: 'Retourne les partenaires autorisés pour l’utilisateur authentifié',
        summary: 'Liste des partenaires autorisés',
        security: [['Bearer' => []]],
        tags: ['Permissions']
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Utilisateurs et partenaires autorisés',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'email',
                    description: 'Email de l’utilisateur connecté',
                    type: 'string',
                    example: 'user@example.com'
                ),
                new OA\Property(
                    property: 'authorizedPartners',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(
                                property: 'id',
                                type: 'integer',
                                example: 12
                            ),
                            new OA\Property(
                                property: 'zipCode',
                                type: 'string',
                                example: 13
                            ),
                            new OA\Property(
                                property: 'nom',
                                type: 'string',
                                example: 'Ville de Marseille'
                            ),
                            new OA\Property(
                                property: 'type',
                                type: 'string',
                                example: 'COMMUNE_SCHS'
                            ),
                        ],
                        type: 'object'
                    )
                ),
            ],
            type: 'object'
        )
    )]
    public function __invoke(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $authorizedPartners = $this->partnerAuthorizedResolver->resolveBy($user);
        $authorizedPartnersDto = array_map(fn ($authorizedPartner) => new Partner($authorizedPartner), $authorizedPartners);
        $userResponse = new UserMeResponse(
            email: $user->getEmail(), partners: $authorizedPartnersDto
        );

        return $this->json(
            $userResponse,
            Response::HTTP_OK,
            [],
            ['groups' => 'user:me']
        );
    }
}
