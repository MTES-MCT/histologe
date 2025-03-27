<?php

namespace App\Controller\Api;

use App\Dto\Api\Request\SuiviRequest;
use App\Dto\Api\Response\SuiviResponse;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\EventListener\SecurityApiExceptionListener;
use App\Manager\SuiviManager;
use App\Service\Signalement\DescriptionFilesBuilder;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[When('dev')]
#[When('test')]
#[Route('/api')]
class SuiviCreateController extends AbstractController
{
    public function __construct(
        readonly private SuiviManager $suiviManager,
        readonly private DescriptionFilesBuilder $descriptionFilesBuilder,
        readonly private ValidatorInterface $validator,
    ) {
    }

    #[Route('/signalements/{uuid:signalement}/suivis', name: 'api_signalements_suivis_post', methods: ['POST'])]
    #[OA\Post(
        path: '/api/signalements/{uuid}/suivis',
        description: 'Création d\'un suivi',
        summary: 'Création d\'un suivi',
        security: [['Bearer' => []]],
        tags: ['Suivis']
    )]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Suivi créé avec succès',
        content: new OA\JsonContent(ref: new Model(type: SuiviResponse::class))
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
                    property: 'statut',
                    type: 'int',
                    example: Response::HTTP_NOT_FOUND
                ),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: Response::HTTP_BAD_REQUEST,
        description: 'Mauvaise payload (données invalides).',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Valeurs invalides pour les champs suivants :'
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
                                example: 'description'
                            ),
                            new OA\Property(
                                property: 'message',
                                type: 'string',
                                example: 'Le contenu du suivi doit faire au moins 10 caractères !'
                            ),
                        ],
                        type: 'object'
                    )
                ),
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: Response::HTTP_FORBIDDEN,
        description: 'Accès à la ressource non autorisé.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Vous n\'avez pas l\'autorisation d\'accéder à cette ressource.'
                ),
                new OA\Property(
                    property: 'statut',
                    type: 'int',
                    example: Response::HTTP_FORBIDDEN
                ),
            ],
            type: 'object'
        )
    )]
    public function __invoke(
        #[MapRequestPayload(validationGroups: ['false'])]
        SuiviRequest $suiviRequest,
        ?Signalement $signalement = null,
    ): JsonResponse {
        if (null === $signalement) {
            return $this->json(
                ['message' => 'Signalement introuvable', 'status' => Response::HTTP_NOT_FOUND],
                Response::HTTP_NOT_FOUND
            );
        }
        $this->denyAccessUnlessGranted('COMMENT_CREATE',
            $signalement,
            SecurityApiExceptionListener::ACCESS_DENIED
        );

        $errors = $this->validator->validate($suiviRequest);
        if (count($errors) > 0) {
            throw new ValidationFailedException($suiviRequest, $errors);
        }

        /** @var User $user */
        $user = $this->getUser();
        $suivi = $this->suiviManager->createSuivi(
            signalement: $signalement,
            description: $this->descriptionFilesBuilder->build($signalement, $suiviRequest),
            type: Suivi::TYPE_PARTNER,
            isPublic: $suiviRequest->notifyUsager,
            user: $user,
        );

        return $this->json(new SuiviResponse($suivi), Response::HTTP_CREATED);
    }
}
