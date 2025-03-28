<?php

namespace App\Controller\Api;

use App\Dto\Api\Model\Visite as InterventionModel;
use App\Dto\Api\Request\VisiteRequest;
use App\Entity\Intervention;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\InterventionCreatedEvent;
use App\EventListener\SecurityApiExceptionListener;
use App\Factory\Api\VisiteFactory;
use App\Factory\SignalementVisiteRequestFactory;
use App\Manager\InterventionManager;
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
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[When('dev')]
#[When('test')]
#[Route('/api')]
class VisiteCreateController extends AbstractController
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly InterventionManager $interventionManager,
        private readonly VisiteFactory $interventionFactory,
        private readonly SignalementVisiteRequestFactory $signalementVisiteRequestFactory,
        private readonly ValidatorInterface $validator,
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
            content: new OA\JsonContent(
                examples: [
                    new OA\Examples(
                        example: 'Visite planifiée',
                        summary: 'Visite planifiée',
                        description: 'Exemple d\'une visite prévue.',
                        value: [
                            'date' => '2055-06-15',
                            'time' => '10:00',
                        ]
                    ),
                    new OA\Examples(
                        example: 'Visite confirmée',
                        summary: 'Visite confirmée',
                        description: 'Exemple d\'une visite qui a deja été effectuée.',
                        value: [
                            'date' => '2024-03-01',
                            'time' => '10:00',
                            'visiteEffectuee' => true,
                            'occupantPresent' => true,
                            'proprietairePresent' => false,
                            'notifyUsager' => true,
                            'details' => 'Lorem ipsum dolor sit amet.',
                            'concludeProcedure' => ['LOGEMENT_DECENT'],
                        ]
                    ),
                ],
                ref: '#/components/schemas/VisiteRequest'
            ),
        ),
        tags: ['Visites'],
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
    public function __invoke(
        #[MapRequestPayload(validationGroups: ['false'])]
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

        $errors = $this->validator->validate($visiteRequest);
        if (count($errors) > 0) {
            throw new ValidationFailedException($visiteRequest, $errors);
        }

        /** @var User $user */
        $user = $this->getUser();
        $partner = $user->getPartnerInTerritory($signalement->getTerritory());
        $interventionVisitePlanned = $this->getInterventionVisitePlanned($signalement, $partner);
        if (false !== $interventionVisitePlanned) {
            $message = sprintf(
                'Le partenaire %s a déjà une visite en cours. (uuid:%s).',
                $partner->getNom(),
                $interventionVisitePlanned->getUuid()
            );

            return $this->json(
                ['message' => $message, 'status' => Response::HTTP_BAD_REQUEST],
                Response::HTTP_BAD_REQUEST
            );
        }

        $signalementVisiteRequest = $this->signalementVisiteRequestFactory->createFrom($visiteRequest, $signalement);
        $intervention = $this->interventionManager->createVisiteFromRequest($signalement, $signalementVisiteRequest);

        $timezone = $signalement->getTerritory()?->getTimezone() ?? TimezoneProvider::TIMEZONE_EUROPE_PARIS;
        if ($this->isScheduledInFuture($intervention->getScheduledAt(), $timezone)) {
            $this->eventDispatcher->dispatch(new InterventionCreatedEvent($intervention, $user), InterventionCreatedEvent::NAME);
        }

        return $this->json($this->interventionFactory->createInstance($intervention), Response::HTTP_CREATED);
    }

    /**
     * @throws \DateInvalidTimeZoneException
     */
    private function isScheduledInFuture(\DateTimeImmutable $scheduledAt, string $timezone): bool
    {
        return $scheduledAt->setTimezone(new \DateTimeZone($timezone))->format('Y-m-d') > (new \DateTimeImmutable())->format('Y-m-d');
    }

    private function getInterventionVisitePlanned(Signalement $signalement, Partner $partner): false|Intervention
    {
        return $signalement->getInterventions()
            ->filter(fn (Intervention $intervention) => Intervention::STATUS_PLANNED === $intervention->getStatus()
                && $intervention->getPartner() === $partner)
            ->first();
    }
}
