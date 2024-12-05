<?php

namespace App\Controller\Api;

use App\Dto\Api\Response\SignalementResponse;
use App\Factory\Api\SignalementResponseFactory;
use App\Repository\SignalementRepository;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[When('dev')]
#[When('test')]
#[Route('/api')]
class SignalementController extends AbstractController
{
    #[Route('/signalements', name: 'api_signalements', methods: ['GET'])]
    #[OA\Get(
        path: '/api/signalements',
        description: 'Retourne les {{ limit }} derniers signalements',
        summary: 'Liste des signalements',
        security: [['bearerAuth' => []]],
        tags: ['Signalements'],
    )]
    #[OA\Parameter(
        name: 'limit',
        description: 'Nombre de signalements à retourner (défaut : 20, max : 100)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'limit', example: '10')
    )]
    #[OA\Parameter(
        name: 'page',
        description: 'Numéro de la page de signalement à retourner (défaut : 1)',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'page', example: '2')
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Une liste de signalements',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: SignalementResponse::class))
        )
    )]
    public function getSignalementList(
        SignalementRepository $signalementRepository,
        SignalementResponseFactory $signalementResponseFactory,
        #[MapQueryParameter] int $limit = 20,
        #[MapQueryParameter] int $page = 1,
    ): JsonResponse {
        if ($limit < 1) {
            $limit = 1;
        }
        if ($limit > 100) {
            $limit = 100;
        }
        if ($page < 1) {
            $page = 1;
        }
        $signalements = $signalementRepository->findForAPI(user: $this->getUser(), limit: $limit, page: $page);
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
        security: [['bearerAuth' => []]],
        tags: ['Signalements']
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Un signalement',
        content: new OA\JsonContent(ref: '#/components/schemas/SignalementResponse')
    )]
    public function getSignalementByUuid(
        SignalementRepository $signalementRepository,
        SignalementResponseFactory $signalementResponseFactory,
        string $uuid,
    ): JsonResponse {
        $signalements = $signalementRepository->findForAPI(user : $this->getUser(), uuid : $uuid);
        if (!count($signalements)) {
            return new JsonResponse(['message' => 'Signalement introuvable'], Response::HTTP_NOT_FOUND);
        }
        $resource = $signalementResponseFactory->createFromSignalement($signalements[0]);

        return new JsonResponse($resource, Response::HTTP_OK);
    }
}
