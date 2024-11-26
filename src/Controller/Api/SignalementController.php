<?php

namespace App\Controller\Api;

use App\Dto\Api\Response\SignalementResponse;
use App\Repository\SignalementRepository;
use App\Service\Signalement\SignalementDesordresProcessor;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
        SignalementDesordresProcessor $signalementDesordresProcessor,
        UrlGeneratorInterface $urlGenerator,
        #[MapQueryParameter] int $limit = 20,
    ): JsonResponse {
        if ($limit > 100) {
            $limit = 100;
        }
        $signalements = $signalementRepository->findForAPI(user: $this->getUser(), limit: $limit);
        $resources = [];
        foreach ($signalements as $signalement) {
            $resources[] = new SignalementResponse($signalement, $signalementDesordresProcessor, $urlGenerator); // sinon comment acceder à ses services pour generer les responses ?
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
        SignalementDesordresProcessor $signalementDesordresProcessor,
        UrlGeneratorInterface $urlGenerator,
        string $uuid,
    ): JsonResponse {
        $signalements = $signalementRepository->findForAPI(user : $this->getUser(), uuid : $uuid);
        if (!count($signalements)) {
            return new JsonResponse(['message' => 'Signalement introuvable'], Response::HTTP_NOT_FOUND);
        }
        $resource = new SignalementResponse($signalements[0], $signalementDesordresProcessor, $urlGenerator);

        return new JsonResponse($resource, Response::HTTP_OK);
    }

    #[Route('/signalements/reference/{reference}', name: 'api_signalement_reference', methods: ['GET'])]
    #[OA\Get(
        path: '/api/signalements/reference/{reference}',
        description: 'Retourne un signalement récupéré par sa reference',
        summary: 'Signalement par référence',
        security: [['bearerAuth' => []]],
        tags: ['Signalements']
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Un signalement',
        content: new OA\JsonContent(ref: '#/components/schemas/SignalementResponse')
    )]
    public function getSignalementByReference(
        SignalementRepository $signalementRepository,
        SignalementDesordresProcessor $signalementDesordresProcessor,
        UrlGeneratorInterface $urlGenerator,
        string $reference,
    ): JsonResponse {
        $signalements = $signalementRepository->findForAPI(user : $this->getUser(), reference : $reference);
        if (!count($signalements)) {
            return new JsonResponse(['message' => 'Signalement introuvable'], Response::HTTP_NOT_FOUND);
        }
        $resource = new SignalementResponse($signalements[0], $signalementDesordresProcessor, $urlGenerator);

        return new JsonResponse($resource, Response::HTTP_OK);
    }
}
