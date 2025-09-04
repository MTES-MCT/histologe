<?php

namespace App\Controller\Api;

use App\Dto\Api\Request\ArreteRequest;
use App\Dto\Api\Response\ArreteResponse;
use App\Entity\Enum\ProcedureType;
use App\Entity\Enum\Qualification;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\InterventionCreatedEvent;
use App\EventListener\SecurityApiExceptionListener;
use App\Manager\InterventionManager;
use App\Security\Voter\Api\ApiSignalementPartnerVoter;
use App\Service\Security\UserApiPermissionService;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class ArreteCreateController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly InterventionManager $interventionManager,
        private readonly SignalementQualificationUpdater $signalementQualificationUpdater,
        private readonly UserApiPermissionService $userApiPermissionService,
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     */
    #[OA\Post(
        path: '/api/signalements/{uuid}/arretes',
        description: 'Création d\'un arrêté préfectoral',
        summary: 'Création d\'un arrêté préfectoral',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Payload d\'un arrêté préfectoral',
            content: new OA\JsonContent(
                examples: [
                    new OA\Examples(
                        example: 'Création d\'un arrêté',
                        summary: 'Création d\'un arrêté',
                        description: 'Exemple d\'un arrêté préfectoral.',
                        value: [
                            'date' => '2021-01-01',
                            'numero' => '123456789',
                            'numeroDossier' => '2023/DD13/00664',
                            'type' => 'Arrêté L.511-11 - Suroccupation',
                        ]
                    ),
                    new OA\Examples(
                        example: 'Création d\'un arrêté avec une mainlevée',
                        summary: 'Création d\'un arrêté avec une mainlevée',
                        description: 'Exemple d\'un arrêté préfectoral avec une mainlevée.',
                        value: [
                            'date' => '2021-01-01',
                            'numero' => '123456789',
                            'numeroDossier' => '2023/DD13/00664',
                            'type' => 'Arrêté L.511-11 - Suroccupation',
                            'mainLeveeDate' => '2023-01-01',
                            'mainLeveeNumero' => '123456789',
                        ]
                    ),
                ],
                ref: '#/components/schemas/ArreteRequest',
            )
        ),
        tags: ['Arrêtés'],
    )]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Arrêté crée avec succès',
        content: new OA\JsonContent(ref: new Model(type: ArreteResponse::class))
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
    #[Route('/signalements/{uuid:signalement}/arretes', name: 'api_signalements_arretes_post', methods: 'POST')]
    public function __invoke(
        #[MapRequestPayload(validationGroups: ['false'])]
        ArreteRequest $arreteRequest,
        ?Signalement $signalement = null,
    ): JsonResponse {
        if (null === $signalement) {
            return $this->json(
                ['message' => 'Signalement introuvable', 'status' => Response::HTTP_NOT_FOUND],
                Response::HTTP_NOT_FOUND
            );
        }
        /** @var User $user */
        $user = $this->getUser();
        $partner = $this->userApiPermissionService->getUniquePartner($user);
        if (!$partner) {
            throw new \Exception('TODO permission API : ajout du parametre partenaire facultatif. Si non fournit renvoyer une erreur demandant de l\'expliciter');
        }
        $this->denyAccessUnlessGranted(
            ApiSignalementPartnerVoter::API_ADD_INTERVENTION,
            ['signalement' => $signalement, 'partner' => $partner],
            SecurityApiExceptionListener::ACCESS_DENIED
        );
        $affectation = $signalement->getAffectationForPartner($partner);

        $errors = $this->validator->validate($arreteRequest);
        if (count($errors) > 0) {
            throw new ValidationFailedException($arreteRequest, $errors);
        }
        $isNew = false;
        $intervention = $this->interventionManager->createArreteFromRequest($arreteRequest, $affectation, $isNew);
        if ($isNew) {
            $signalement->addIntervention($intervention);
            if (!$signalement->hasQualificaton(Qualification::INSALUBRITE)) {
                $this->signalementQualificationUpdater->updateQualificationFromVisiteProcedureList(
                    $signalement,
                    [ProcedureType::INSALUBRITE]
                );
            }
        }
        $interventionCreatedEvent = $this->eventDispatcher->dispatch(
            new InterventionCreatedEvent($intervention, $user),
            InterventionCreatedEvent::NAME
        );
        $suivi = $interventionCreatedEvent->getSuivi();

        return $this->json(new ArreteResponse($intervention, $suivi), Response::HTTP_CREATED);
    }
}
