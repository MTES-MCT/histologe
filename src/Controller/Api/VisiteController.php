<?php

namespace App\Controller\Api;

use App\Dto\Api\Model\Intervention as InterventionModel;
use App\Dto\Api\Request\VisiteRequest;
use App\Dto\Request\Signalement\VisiteRequest as SignalementVisiteRequest;
use App\Entity\Affectation;
use App\Entity\Intervention;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\InterventionCreatedEvent;
use App\EventListener\SecurityApiExceptionListener;
use App\Exception\Intervention\VisitePartnerAlreadyPlannedException;
use App\Factory\Api\InterventionFactory;
use App\Manager\InterventionManager;
use App\Service\Signalement\DescriptionFilesBuilder;
use App\Service\TimezoneProvider;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;

#[When('dev')]
#[When('test')]
#[Route('/api')]
class VisiteController extends AbstractController
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly InterventionManager $interventionManager,
        private readonly InterventionFactory $interventionFactory,
        private readonly DescriptionFilesBuilder $descriptionFilesBuilder,
    ) {
    }

    /**
     * @throws \Exception
     */
    #[OA\Post(
        path: '/api/signalements/{uuid}/visites',
        description: 'Création d\'une visite',
        summary: 'Création d\'une visite',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Payload d\'une visite',
            content: new OA\JsonContent(ref: new Model(type: VisiteRequest::class)),
        ),
        tags: ['Interventions'],
    )]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Visite crée avec succès',
        content: new OA\JsonContent(ref: new Model(type: InterventionModel::class))
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
    #[Route('/signalements/{uuid:signalement}/visites', name: 'api_signalements_visite_post', methods: 'POST')]
    public function create(
        #[MapRequestPayload]
        VisiteRequest $visiteRequest,
        ?Signalement $signalement = null,
    ): JsonResponse {
        if (null === $signalement) {
            return $this->json(
                ['message' => 'Signalement introuvable', 'status' => Response::HTTP_NOT_FOUND],
                Response::HTTP_NOT_FOUND
            );
        }

        $this->denyAccessUnlessGranted(
            'SIGN_ADD_VISITE',
            $signalement,
            SecurityApiExceptionListener::ACCESS_DENIED
        );

        $signalementVisiteRequest = $this->createSignalementVisiteRequest($signalement, $visiteRequest);
        try {
            $intervention = $this->interventionManager->createVisiteFromRequest($signalement, $signalementVisiteRequest);
        } catch (VisitePartnerAlreadyPlannedException $exception) {
            return $this->json(
                ['message' => $exception->getMessage(), 'status' => Response::HTTP_BAD_REQUEST],
                Response::HTTP_BAD_REQUEST
            );
        }

        $timezone = $signalement->getTerritory()?->getTimezone() ?? TimezoneProvider::TIMEZONE_EUROPE_PARIS;
        if ($this->isScheduledInFuture($intervention->getScheduledAt(), $timezone)) {
            /** @var User $user */
            $user = $this->getUser();
            $this->eventDispatcher->dispatch(new InterventionCreatedEvent($intervention, $user), InterventionCreatedEvent::NAME);
        }

        return $this->json($this->interventionFactory->createInstance($intervention), Response::HTTP_CREATED);
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \Exception
     */
    #[OA\Put(
        path: '/api/interventions/{uuid}/visites',
        description: 'Confirmation d\'une visite',
        summary: 'Confirmation d\'une visite',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Payload d\'une visite pour confirmation de visite.',
            content: new OA\JsonContent(ref: new Model(type: VisiteRequest::class)),
        ),
        tags: ['Interventions'],
    )]
    #[OA\Tag(name: 'Interventions')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Visite mise à jour avec succès',
        content: new OA\JsonContent(ref: new Model(type: InterventionModel::class))
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
        response: Response::HTTP_UNAUTHORIZED,
        description: 'Accès non autorisé à la ressource.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 401),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthorized'),
            ]
        )
    )]
    #[OA\Response(
        response: Response::HTTP_NOT_FOUND,
        description: 'Intervention non trouvé.',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'integer', example: 404),
                new OA\Property(property: 'message', type: 'string', example: 'Resource Not Found'),
            ]
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
    #[Route('/interventions/{uuid:intervention}',
        name: 'api_signalements_visite_put',
        methods: 'PUT')]
    public function update(
        #[MapRequestPayload(validationGroups: 'PUT_VISITE')]
        VisiteRequest $visiteRequest,
        ?Intervention $intervention = null,
    ): JsonResponse {
        if (null === $intervention) {
            return $this->json([
                'message' => 'Intervention introuvable.',
                'status' => Response::HTTP_NOT_FOUND],
                Response::HTTP_NOT_FOUND
            );
        }
        $this->denyAccessUnlessGranted('INTERVENTION_EDIT_VISITE', $intervention, SecurityApiExceptionListener::ACCESS_DENIED);

        if ($response = $this->checkIfVisiteAlreadyConfirmed($intervention)) {
            return $response;
        }

        $signalement = $intervention->getSignalement();
        $signalementVisiteRequest = $this->createSignalementVisiteRequest($signalement, $visiteRequest);

        $intervention->setScheduledAt(new \DateTimeImmutable($signalementVisiteRequest->getDateTimeUTC()));
        try {
            $this->interventionManager->confirmVisiteFromRequest($signalementVisiteRequest, $intervention);
        } catch (NotEnabledTransitionException $exception) {
            return $this->json([
                'message' => 'La visite a déjà été confirmée, cette action n\'est plus possible.',
                'status' => Response::HTTP_BAD_REQUEST,
                'errors' => $exception->getMessage(),
            ],
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->json($this->interventionFactory->createInstance($intervention), Response::HTTP_OK);
    }

    private function getAffectation(Signalement $signalement): Affectation
    {
        /** @var User $user */
        $user = $this->getUser();

        return $signalement
            ->getAffectations()
            ->filter(function (Affectation $affectation) use ($user) {
                return $user->hasPartner($affectation->getPartner());
            })
            ->current();
    }

    /**
     * @throws \Exception
     */
    private function createSignalementVisiteRequest(
        Signalement $signalement,
        VisiteRequest $visiteRequest,
    ): SignalementVisiteRequest {
        $affectation = $this->getAffectation($signalement);

        return new SignalementVisiteRequest(
            date: $visiteRequest->date,
            time: $visiteRequest->time,
            timezone: $signalement->getTimezone(),
            idPartner: $affectation->getPartner()->getId(),
            details: $this->descriptionFilesBuilder->build($signalement, $visiteRequest),
            concludeProcedure: $visiteRequest->concludeProcedure,
            isVisiteDone: true,
            isOccupantPresent: $visiteRequest->occupantPresent,
            isProprietairePresent: $visiteRequest->proprietairePresent,
            isUsagerNotified: $visiteRequest->notifyUsager
        );
    }

    /**
     * @throws \DateInvalidTimeZoneException
     */
    private function isScheduledInFuture(\DateTimeImmutable $scheduledAt, string $timezone): bool
    {
        return $scheduledAt->setTimezone(new \DateTimeZone($timezone))->format('Y-m-d') > (new \DateTimeImmutable())->format('Y-m-d');
    }

    private function checkIfVisiteAlreadyConfirmed(Intervention $intervention): ?JsonResponse
    {
        if (Intervention::STATUS_DONE === $intervention->getStatus()) {
            return $this->json([
                'message' => 'La visite a déjà été confirmée, cette action n\'est plus possible.',
                'status' => Response::HTTP_BAD_REQUEST,
            ], Response::HTTP_BAD_REQUEST);
        }

        return null;
    }
}
