<?php

namespace App\Controller\Api;

use App\Dto\Api\Request\AffectationRequest;
use App\Dto\Api\Response\AffectationResponse;
use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\MotifRefus;
use App\Entity\User;
use App\EventListener\SecurityApiExceptionListener;
use App\Manager\AffectationManager;
use App\Security\Voter\AffectationVoter;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class AffectationUpdateController extends AbstractController
{
    public function __construct(
        private readonly AffectationManager $affectationManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/affectations/{uuid:affectation}', name: 'api_affectations_update', methods: 'PATCH')]
    #[OA\Patch(
        path: '/api/affectations/{uuid}',
        description: 'Mise à jour d\'une affectation',
        summary: 'Mise à jour d\'une affectation',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                examples: [
                    new OA\Examples(
                        example: 'Dossier en cours',
                        summary: 'Affectation passant de NOUVEAU à EN_COURS',
                        value: [
                            'statut' => 'EN_COURS',
                        ]
                    ),
                    new OA\Examples(
                        example: 'Dossier fermé',
                        summary: 'Affectation passant EN_COURS à FERME',
                        value: [
                            'statut' => 'FERME',
                            'motifCloture' => 'REFUS_DE_VISITE',
                            'message' => 'Lorem ipsum dolor sit amet',
                        ]
                    ),
                    new OA\Examples(
                        example: 'Dossier refusé',
                        summary: 'Affectation passant de NOUVEAU à REFUSE',
                        value: [
                            'statut' => 'FERME',
                            'motifCloture' => 'REFUS_DE_VISITE',
                            'message' => 'Lorem ipsum dolor sit amet',
                        ]
                    ),
                    new OA\Examples(
                        example: 'Dossier de nouveau ouvert',
                        summary: 'Affectation passant de FERME à NOUVEAU',
                        value: [
                            'statut' => 'NOUVEAU',
                            'notifyUsager' => true,
                        ]
                    ),
                ],
                ref: '#/components/schemas/AffectationRequest'
            ),
        ),
        tags: ['Affectations']
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Une affectation',
        content: new OA\JsonContent(ref: '#/components/schemas/Affectation')
    )]
    #[OA\Response(
        response: Response::HTTP_NOT_FOUND,
        description: 'Affectation introuvable',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Affectation introuvable'
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
                                example: 'statut'
                            ),
                            new OA\Property(
                                property: 'message',
                                type: 'string',
                                example: 'Cette valeur doit être l\'un des choix suivants : \"NOUVEAU\", \"EN_COURS\", \"FERME\", \"REFUSE\"'
                            ),
                            new OA\Property(
                                property: 'invalidValue',
                                type: 'string',
                                example: 'NOUVEAddU'
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
        AffectationRequest $affectationRequest,
        ?Affectation $affectation = null,
    ): JsonResponse {
        if (null === $affectation) {
            return new JsonResponse(
                ['message' => 'Affectation introuvable.', 'status' => Response::HTTP_NOT_FOUND],
                Response::HTTP_NOT_FOUND
            );
        }

        $this->denyAccessUnlessGranted(AffectationVoter::ANSWER, $affectation, SecurityApiExceptionListener::ACCESS_DENIED);
        $errors = $this->validator->validate($affectationRequest);
        if (count($errors) > 0) {
            throw new ValidationFailedException($affectationRequest, $errors);
        }

        $affectation->setNextStatut(AffectationStatus::tryFrom($affectationRequest->statut));
        $this->denyAccessUnlessGranted(AffectationVoter::UPDATE_STATUT, $affectation, SecurityApiExceptionListener::TRANSITION_STATUT_DENIED);
        $this->applyUsagerNotification($affectationRequest, $affectation);

        $affectation = $this->update($affectationRequest, $affectation);

        return new JsonResponse(new AffectationResponse($affectation), Response::HTTP_OK);
    }

    private function update(AffectationRequest $affectationRequest, Affectation $affectation): Affectation
    {
        /** @var User $user */
        $user = $this->getUser();

        $statut = $affectation->getNextStatut();
        if (AffectationStatus::CLOSED === $statut) {
            $motifCloture = MotifCloture::tryFrom($affectationRequest->motifCloture);

            return $this->affectationManager->closeAffectation(
                affectation: $affectation,
                user: $user,
                motif: $motifCloture,
                message: $affectationRequest->message,
                flush: true
            );
            // TODO : suppression des abonnements ?
        }
        $motifRefus = $message = null;
        if (AffectationStatus::REFUSED === $statut) {
            $motifRefus = MotifRefus::tryFrom($affectationRequest->motifRefus);
            $message = $affectationRequest->message;
        }

        // TODO : création des abonnements ?
        return $this->affectationManager->updateAffectation($affectation, $user, $statut, $motifRefus, $message);
    }

    private function applyUsagerNotification(AffectationRequest $affectationRequest, Affectation $affectation): void
    {
        if (AffectationStatus::CLOSED === $affectation->getStatut()
            && AffectationStatus::WAIT === $affectation->getNextStatut()) {
            $affectation->setHasNotificationUsagerToCreate($affectationRequest->notifyUsager);
        }
    }
}
