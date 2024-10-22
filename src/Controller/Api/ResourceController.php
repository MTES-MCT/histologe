<?php

namespace App\Controller\Api;

use App\Dto\Api\Request\ResourceCreateRequest;
use App\Dto\Api\Response\ResourceResponse;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[When('dev')]
#[When('test')]
#[Route('/api')]
class ResourceController extends AbstractController
{
    #[Route('/resources', name: 'api_ressources', methods: ['GET'])]
    #[OA\Get(
        path: '/api/resources',
        description: 'Fetches an array of resources with their details',
        summary: 'Retrieve a list of resources',
        security: [["bearerAuth" => []]],
        tags: ['Resources'],
    )]
    #[OA\Parameter(
        name: 'limit',
        description: 'Key parameter to filter the resources',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'limit', example: '10')
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'A list of resources',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: ResourceResponse::class))
        )
    )]
    public function getResourceList(
        #[MapQueryParameter] int $limit = 3,
    ): JsonResponse {
        $faker = \Faker\Factory::create();
        $resources = [];
        for ($i = 0; $i < $limit; ++$i) {
            $resources[] = new ResourceResponse($faker->uuid(), $faker->sentence());
        }

        return new JsonResponse($resources, Response::HTTP_OK);
    }

    #[Route('/resources/{uuid}', name: 'api_resource', methods: ['GET'])]
    #[OA\Get(
        path: '/api/resources/{uuid}',
        description: 'Fetches a single resource by UUID',
        summary: 'Retrieve a single resource',
        security: [["bearerAuth" => []]],
        tags: ['Resources']
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'A single resource',
        content: new OA\JsonContent(ref: '#/components/schemas/ResourceResponse')
    )]
    public function getResource(string $uuid): JsonResponse
    {
        $faker = \Faker\Factory::create();
        $resource = new ResourceResponse($uuid, $faker->sentence());

        return new JsonResponse($resource, Response::HTTP_OK);
    }

    #[Route('/resources', name: 'api_create_resource', methods: ['POST'])]
    #[OA\Post(
        path: '/api/resources',
        description: 'Create a new resource',
        summary: 'Create a resource',
        security: [["bearerAuth" => []]],
        tags: ['Resources']
    )]
    #[OA\RequestBody(
        description: 'Payload to create a resource',
        required: true,
    )]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Resource created',
        content: new OA\JsonContent(ref: '#/components/schemas/ResourceResponse')
    )]
    public function createResource(#[MapRequestPayload] ResourceCreateRequest $resourceCreateRequest): JsonResponse
    {
        $resourceCreateRequest->message = 'Hello World!';
        $resourceResponse = new ResourceResponse(message: $resourceCreateRequest->message);

        return new JsonResponse($resourceResponse, Response::HTTP_CREATED);
    }
}
