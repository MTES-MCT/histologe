<?php

namespace App\Controller\Api;

use App\Dto\Api\Request\SignalementListQueryParams;
use App\Dto\Api\Response\SignalementResponse;
use App\Entity\User;
use App\Factory\Api\SignalementResponseFactory;
use App\Repository\SignalementRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class SignalementListController extends AbstractController
{
    /**
     * @throws \DateMalformedStringException
     */
    #[Route('/signalements', name: 'api_signalements', methods: ['GET'])]
    #[OA\Get(
        path: '/api/signalements',
        description: 'Retourne une liste des signalements les plus récents, triés par date de dépôt en ordre décroissant.',
        summary: 'Liste des signalements',
        security: [['Bearer' => []]],
        tags: ['Signalements'],
    )]
    #[OA\Parameter(
        name: 'query',
        description: 'Filtres de recherche pour les signalements',
        in: 'query',
        required: false,
        content: new OA\JsonContent(ref: new Model(type: SignalementListQueryParams::class))
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Une liste de signalements',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: SignalementResponse::class))
        )
    )]
    #[OA\Response(
        response: Response::HTTP_BAD_REQUEST,
        description: 'Mauvaise requête (données invalides).',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Valeurs invalides pour les filtres suivants :'
                ),
                new OA\Property(
                    property: 'status',
                    type: 'integer',
                    example: 400
                ),
                new OA\Property(
                    property: 'errors',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(
                                property: 'property',
                                type: 'string',
                                example: 'limit'
                            ),
                            new OA\Property(
                                property: 'message',
                                type: 'string',
                                example: 'La limite ne peut pas dépasser 100.'
                            ),
                            new OA\Property(
                                property: 'invalidValue',
                                type: 'integer',
                                example: 454544
                            ),
                        ],
                        type: 'object'
                    )
                ),
            ],
            type: 'object'
        )
    )]
    public function getSignalementList(
        SignalementRepository $signalementRepository,
        SignalementResponseFactory $signalementResponseFactory,
        #[MapQueryString] ?SignalementListQueryParams $signalementListQueryParams = null,
    ): JsonResponse {
        $signalementListQueryParams ??= new SignalementListQueryParams();
        /** @var User $user */
        $user = $this->getUser();
        $signalements = $signalementRepository->findAllForApi(
            user: $user,
            signalementListQueryParams: $signalementListQueryParams
        );
        $resources = [];
        foreach ($signalements as $signalement) {
            $resources[] = $signalementResponseFactory->createFromSignalement($signalement);
        }

        return new JsonResponse($resources, Response::HTTP_OK);
    }

    #[Route('/signalements/{uuid}', name: 'api_signalement_uuid', methods: ['GET'])]
    #[OA\Get(
        path: '/api/signalements/{uuid}',
        description: 'Retourne un signalement récupéré par son UUID',
        summary: 'Signalement par UUID',
        security: [['Bearer' => []]],
        tags: ['Signalements']
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Un signalement',
        content: new OA\JsonContent(ref: '#/components/schemas/SignalementResponse')
    )]
    #[OA\Response(
        response: Response::HTTP_NOT_FOUND,
        description: 'Signalement introuvable',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Signalement introuvable'
                ),
                new OA\Property(
                    property: 'status',
                    type: 'int',
                    example: 404
                ),
            ],
            type: 'object'
        )
    )]
    public function getSignalementByUuid(
        SignalementRepository $signalementRepository,
        SignalementResponseFactory $signalementResponseFactory,
        string $uuid,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $signalement = $signalementRepository->findOneForApi(user : $user, uuid : $uuid);
        if (null !== $signalement) {
            $resource = $signalementResponseFactory->createFromSignalement($signalement);

            return new JsonResponse($resource, Response::HTTP_OK);
        }

        return new JsonResponse(['message' => 'Signalement introuvable', 'status' => Response::HTTP_NOT_FOUND], Response::HTTP_NOT_FOUND);
    }
}
