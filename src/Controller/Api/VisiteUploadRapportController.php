<?php

namespace App\Controller\Api;

use App\Dto\Api\Request\FileUploadRequest;
use App\Entity\Intervention;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[When('dev')]
#[When('test')]
#[Route('/api')]
class VisiteUploadRapportController extends AbstractController
{
    public function __construct(
        private readonly DenormalizerInterface $normalizer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    #[OA\Patch(
        path: '/api/interventions/{uuid}',
        description: 'Téléversement du rapport de visite..',
        summary: 'Téléversement du rapport de visite',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Données de téléversement du fichier',
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        'rapport' => new OA\Property(
                            description: 'Fichier téléversé',
                            type: 'string',
                            format: 'binary'
                        ),
                    ],
                    type: 'object'
                ),
                examples: [
                    new OA\Examples(
                        example: "Exemple d'envoi d'un fichier",
                        summary: "Exemple d'envoi d'un fichier",
                        value: [
                            'rapport' => 'file1.jpg',
                        ]
                    ),
                ]
            )
        ),
        tags: ['Interventions'],
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Un document',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: \App\Dto\Api\Model\Intervention::class))
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Erreur liée à la validation de la requête.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Valeurs invalides pour les champs suivants :'),
                new OA\Property(property: 'status', type: 'integer', example: 400),
                new OA\Property(
                    property: 'errors',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'property', type: 'string', example: 'files'),
                            new OA\Property(property: 'message', type: 'string', example: 'Vous devez téléverser un fichier.'),
                            new OA\Property(property: 'invalidValue', type: 'array', items: new OA\Items(type: 'string'), example: []),
                        ]
                    )
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Accès non autorisé à la ressource.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 401),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthorized'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Signalement non trouvé.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 404),
                new OA\Property(property: 'message', type: 'string', example: 'Resource Not Found'),
            ]
        )
    )]
    #[Route('/interventions/{uuid:intervention}', name: 'api_visites_rapport_visite_patch', methods: 'PATCH')]
    public function __invoke(
        Request $request,
        ?Intervention $intervention = null,
    ): JsonResponse {
        if (null === $intervention) {
            return $this->json(
                ['message' => 'Intervention introuvable', 'status' => Response::HTTP_NOT_FOUND],
                Response::HTTP_NOT_FOUND
            );
        }

        $data['file'] = $request->files->get('file');
        $fileRequest = $this->normalizer->denormalize($data['file'], FileUploadRequest::class, 'json');
        $errors = $this->validator->validate($fileRequest);
        if (count($errors) > 0) {
            throw new ValidationFailedException($fileRequest, $errors);
        }

        /*
         * @todo : Finaliser le téléversement du rapport de visite
         * @see SignalementFileUploadController
         */

        return $this->json([]);
    }
}
