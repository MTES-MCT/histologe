<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\AdresseOccupantRequest;
use App\Dto\Request\Signalement\CompositionLogementRequest;
use App\Dto\Request\Signalement\ConsommationEnergetiqueRequest;
use App\Dto\Request\Signalement\CoordonneesAgenceRequest;
use App\Dto\Request\Signalement\CoordonneesBailleurOldRequest;
use App\Dto\Request\Signalement\CoordonneesBailleurRequest;
use App\Dto\Request\Signalement\CoordonneesFoyerRequest;
use App\Dto\Request\Signalement\CoordonneesSyndicRequest;
use App\Dto\Request\Signalement\CoordonneesTiersRequest;
use App\Dto\Request\Signalement\InformationsBailleurRequest;
use App\Dto\Request\Signalement\InformationsLogementRequest;
use App\Dto\Request\Signalement\InviteTiersRequest;
use App\Dto\Request\Signalement\OccupationLogementRequest;
use App\Dto\Request\Signalement\ProcedureDemarchesOldRequest;
use App\Dto\Request\Signalement\ProcedureDemarchesRequest;
use App\Dto\Request\Signalement\SituationFoyerOldRequest;
use App\Dto\Request\Signalement\SituationFoyerRequest;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Enum\SuiviDelayedType;
use App\Entity\Enum\TiersInvitationStatus;
use App\Entity\Signalement;
use App\Entity\User;
use App\Exception\Address\CityNotFoundException;
use App\Exception\Address\TerritoryInconsistentException;
use App\Factory\SuiviDelayedFactory;
use App\Factory\TiersInvitationFactory;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Repository\TiersInvitationRepository;
use App\Security\Voter\SignalementVoter;
use App\Serializer\SignalementDraftRequestSerializer;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\MessageHelper;
use App\Service\Signalement\PostalCodeHomeChecker;
use App\Service\Signalement\SignalementQualificationNde;
use App\Service\SignalementAddressContentService;
use App\Utils\FormHelper;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/bo/signalements')]
class SignalementEditController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'FEATURE_ORIENTATION')]
        private readonly bool $featureOrientation,
    ) {
    }

    #[Route('/{uuid:signalement}/edit-address', name: 'back_signalement_edit_address', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ADDRESS, subject: 'signalement')]
    public function editAddress(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        SignalementAddressContentService $signalementAddressContentService,
        PostalCodeHomeChecker $postalCodeHomeChecker,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_address_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var AdresseOccupantRequest $adresseOccupantRequest */
        $adresseOccupantRequest = $serializer->deserialize(
            json_encode($payload),
            AdresseOccupantRequest::class,
            'json'
        );

        $errorMessage = FormHelper::getErrorsFromRequest($validator, $adresseOccupantRequest);
        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        try {
            $subscriptionCreated = $signalementManager->updateFromAdresseOccupantRequest($signalement, $adresseOccupantRequest);
        } catch (CityNotFoundException|TerritoryInconsistentException $e) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => $e->getMessage()];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        } catch (\Exception $e) {
            $logger->error('Erreur lors de la mise à jour de l\'adresse du signalement '.$signalement->getId().': '.$e->getMessage());
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => $e->getMessage()];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        if (!$postalCodeHomeChecker->isActiveByInseeCode($signalement->getAddress()->getCityCode())) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => 'Le territoire n\'est pas actif pour le code insee '.$signalement->getAddress()->getCityCode().'.'];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }

        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'L\'adresse du logement a bien été modifiée.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = $signalementAddressContentService->getHtmlTargetContentsForSignalementAddress($signalement);
        $htmlTargetContents[] = [
            'target' => '#list-suivis',
            'content' => $this->renderView('back/signalement/view/suivis.html.twig', ['signalement' => $signalement]),
        ];
        // TODO à la suppression de FEATURE_ORIENTATION : ne garder que la cible du nouvel onglet
        if ($this->featureOrientation) {
            $htmlTargetContents[] = [
                'target' => '#signalement-description-logement-container',
                'content' => $this->renderView('back/signalement/view/details/description-logement.html.twig', ['signalement' => $signalement]),
            ];
        } else {
            $htmlTargetContents[] = [
                'target' => '#signalement-information-composition-container',
                'content' => $this->renderView('back/signalement/view/information/information-composition.html.twig', ['signalement' => $signalement]),
            ];
        }
        $htmlTargetContents[] = [
            'target' => '#signalement-edit-composition-etage-container',
            'content' => $this->renderView('back/signalement/view/panels/_panel-edit-composition-logement-etage.html.twig', ['signalement' => $signalement]),
        ];
        $functions = [['name' => 'applyFilter']];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents, 'functions' => $functions]);
    }

    #[Route('/{uuid:signalement}/edit-coordonnees-tiers', name: 'back_signalement_edit_coordonnees_tiers', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editCoordonneesTiers(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        TiersInvitationRepository $tiersInvitationRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        // On bloque si ça a été crééé par un occupant et qu'aucun mail n'a encore été renseigné (via invitation)
        if ($signalement->isV2() && !$signalement->getIsNotOccupant() && empty($signalement->getMailDeclarant())) {
            throw $this->createAccessDeniedException();
        }
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_coordonnees_tiers_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var CoordonneesTiersRequest $coordonneesTiersRequest */
        $coordonneesTiersRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            CoordonneesTiersRequest::class,
            'json'
        );

        $errorMessage = FormHelper::getErrorsFromRequest($validator, $coordonneesTiersRequest);

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromCoordonneesTiersRequest($signalement, $coordonneesTiersRequest);
        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les coordonnées du tiers déclarant ont bien été modifiées.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $tiersInvitation = $tiersInvitationRepository->findOneBy([
            'signalement' => $signalement,
            'status' => TiersInvitationStatus::WAITING,
        ]);
        // TODO à la suppression de FEATURE_ORIENTATION : ne garder que la cible du nouvel onglet
        if ($this->featureOrientation) {
            $htmlTargetContents = [
                [
                    'target' => '#signalement-coordonnees-tiers-container',
                    'content' => $this->renderView('back/signalement/view/details/coordonnees-tiers.html.twig', [
                        'signalement' => $signalement,
                        'tiersInvitation' => $tiersInvitation,
                    ]),
                ],
            ];
        } else {
            $htmlTargetContents = [
                [
                    'target' => '#signalement-information-tiers-container',
                    'content' => $this->renderView('back/signalement/view/information/information-tiers.html.twig', [
                        'signalement' => $signalement,
                        'tiersInvitation' => $tiersInvitation,
                    ]),
                ],
            ];
        }
        $htmlTargetContents[] = ['target' => '#list-suivis', 'content' => $this->renderView('back/signalement/view/suivis.html.twig', ['signalement' => $signalement])];
        $functions = [['name' => 'applyFilter']];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents, 'functions' => $functions]);
    }

    #[Route('/{uuid:signalement}/edit-invite-tiers', name: 'back_signalement_edit_invite_tiers', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editInviteTiers(
        #[MapRequestPayload(validationGroups: ['false'])]
        InviteTiersRequest $inviteTiersRequest,
        Signalement $signalement,
        Request $request,
        SuiviManager $suiviManager,
        TiersInvitationRepository $tiersInvitationRepository,
        ValidatorInterface $validator,
        NotificationMailerRegistry $notificationMailerRegistry,
        TiersInvitationFactory $tiersInvitationFactory,
        EntityManagerInterface $em,
    ): JsonResponse {
        // On bloque si tiers déjà renseigné ou si créé par tiers
        if (!empty($signalement->getMailDeclarant()) || ($signalement->isV2() && $signalement->getIsNotOccupant())) {
            throw $this->createAccessDeniedException();
        }

        // On bloque si invitation déjà en cours
        $tiersInvitation = $tiersInvitationRepository->findOneBy([
            'signalement' => $signalement,
            'status' => TiersInvitationStatus::WAITING,
        ]);
        if ($tiersInvitation) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => 'Une invitation est déjà en attente pour ce dossier.'];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_invite_tiers_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }

        $errorMessage = FormHelper::getErrorsFromRequest($validator, $inviteTiersRequest);

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }

        $invitation = $tiersInvitationFactory->createInstanceFrom(
            $signalement,
            $inviteTiersRequest->getNom(),
            $inviteTiersRequest->getPrenom(),
            $inviteTiersRequest->getMail(),
            $inviteTiersRequest->getTelephone()
        );

        $em->persist($invitation);

        $notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_INVITE_TIERS,
                to: $inviteTiersRequest->getMail(),
                signalement: $signalement,
                tiersInvitation: $invitation,
            )
        );
        $subscriptionCreated = $suiviManager->addInviteSuiviFromBo($signalement, $inviteTiersRequest);
        $em->flush();

        $flashMessages[] = ['type' => 'success', 'title' => 'Invitation sur le dossier', 'message' => 'Le tiers aidant a bien été invité.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        // TODO à la suppression de FEATURE_ORIENTATION : ne garder que la cible du nouvel onglet
        if ($this->featureOrientation) {
            $htmlTargetContents = [
                [
                    'target' => '#signalement-coordonnees-tiers-container',
                    'content' => $this->renderView('back/signalement/view/details/coordonnees-tiers.html.twig', [
                        'signalement' => $signalement, 'tiersInvitation' => $invitation,
                    ]),
                ],
            ];
        } else {
            $htmlTargetContents = [
                [
                    'target' => '#signalement-information-tiers-container',
                    'content' => $this->renderView('back/signalement/view/information/information-tiers.html.twig', [
                        'signalement' => $signalement, 'tiersInvitation' => $invitation,
                    ]),
                ],
            ];
        }
        $htmlTargetContents[] = ['target' => '#list-suivis', 'content' => $this->renderView('back/signalement/view/suivis.html.twig', ['signalement' => $signalement])];
        $functions = [['name' => 'applyFilter']];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents, 'functions' => $functions]);
    }

    #[Route('/{uuid:signalement}/edit-coordonnees-foyer', name: 'back_signalement_edit_coordonnees_foyer', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editCoordonneesFoyer(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_coordonnees_foyer_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var CoordonneesFoyerRequest $coordonneesFoyerRequest */
        $coordonneesFoyerRequest = $serializer->deserialize(
            json_encode($payload),
            CoordonneesFoyerRequest::class,
            'json'
        );

        $validationGroups = ['Default'];
        if ($signalement->getProfileDeclarant()) {
            $validationGroups[] = $signalement->getProfileDeclarant()->value;
        }
        $errorMessage = FormHelper::getErrorsFromRequest($validator, $coordonneesFoyerRequest, $validationGroups);

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromCoordonneesFoyerRequest($signalement, $coordonneesFoyerRequest);
        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les coordonnées du foyer ont bien été modifiées.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = [
            [
                'target' => '#signalement-title-container',
                'content' => $this->renderView('back/signalement/view/header/_title.html.twig', ['signalement' => $signalement]),
            ],
        ];
        // TODO à la suppression de FEATURE_ORIENTATION : ne garder que les cibles du nouvel onglet
        if ($this->featureOrientation) {
            $htmlTargetContents[] = [
                'target' => '#signalement-coordonnees-foyer-container',
                'content' => $this->renderView('back/signalement/view/details/coordonnees-foyer.html.twig', ['signalement' => $signalement]),
            ];
            $htmlTargetContents[] = [
                'target' => '#signalement-bailleur-agence-syndic-container',
                'content' => $this->renderView('back/signalement/view/details/bailleur-agence-syndic.html.twig', ['signalement' => $signalement]),
            ];
        } else {
            $htmlTargetContents[] = [
                'target' => '#signalement-information-foyer-container',
                'content' => $this->renderView('back/signalement/view/information/information-foyer.html.twig', ['signalement' => $signalement]),
            ];
            $htmlTargetContents[] = [
                'target' => '#signalement-information-bailleur-container',
                'content' => $this->renderView('back/signalement/view/information/information-bailleur.html.twig', ['signalement' => $signalement]),
            ];
            $htmlTargetContents[] = [
                'target' => '#signalement-information-agence-container',
                'content' => $this->renderView('back/signalement/view/information/information-agence.html.twig', ['signalement' => $signalement]),
            ];
        }
        $htmlTargetContents[] = ['target' => '#list-suivis', 'content' => $this->renderView('back/signalement/view/suivis.html.twig', ['signalement' => $signalement])];
        $functions = [['name' => 'applyFilter']];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents, 'functions' => $functions]);
    }

    #[Route('/{uuid:signalement}/edit-coordonnees-bailleur', name: 'back_signalement_edit_coordonnees_bailleur', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editCoordonneesBailleur(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        // TODO à la suppression de FEATURE_ORIENTATION : supprimer ce contrôle
        if (!$this->featureOrientation) {
            throw $this->createNotFoundException();
        }
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_coordonnees_bailleur_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var CoordonneesBailleurRequest $coordonneesBailleurRequest */
        $coordonneesBailleurRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            CoordonneesBailleurRequest::class,
            'json'
        );
        $validationGroups = ['Default'];
        if ($signalement->getProfileDeclarant()) {
            $validationGroups[] = $signalement->getProfileDeclarant()->value;
        }
        $errorMessage = FormHelper::getErrorsFromRequest(
            $validator,
            $coordonneesBailleurRequest,
            $validationGroups
        );

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromCoordonneesBailleurRequest($signalement, $coordonneesBailleurRequest);
        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les coordonnées du bailleur ont bien été modifiées.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = [
            [
                'target' => '#signalement-coordonnees-bailleur-container',
                'content' => $this->renderView('back/signalement/view/details/coordonnees-bailleur.html.twig', ['signalement' => $signalement]),
            ],
        ];
        $htmlTargetContents[] = ['target' => '#list-suivis', 'content' => $this->renderView('back/signalement/view/suivis.html.twig', ['signalement' => $signalement])];
        $functions = [['name' => 'applyFilter']];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents, 'functions' => $functions]);
    }

    // TODO à la suppression de FEATURE_ORIENTATION :
    // - supprimer cette route, son DTO CoordonneesBailleurOldRequest et la méthode SignalementManager::updateFromCoordonneesBailleurOldRequest
    // - supprimer le panel _panel-edit-coordonnees-bailleur-old.html.twig et les tests associés
    #[Route('/{uuid:signalement}/edit-coordonnees-bailleur-old', name: 'back_signalement_edit_coordonnees_bailleur_old', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editCoordonneesBailleurOld(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_coordonnees_bailleur_old_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var CoordonneesBailleurOldRequest $coordonneesBailleurOldRequest */
        $coordonneesBailleurOldRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            CoordonneesBailleurOldRequest::class,
            'json'
        );
        $validationGroups = ['Default'];
        if ($signalement->getProfileDeclarant()) {
            $validationGroups[] = $signalement->getProfileDeclarant()->value;
        }
        $errorMessage = FormHelper::getErrorsFromRequest(
            $validator,
            $coordonneesBailleurOldRequest,
            $validationGroups
        );

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromCoordonneesBailleurOldRequest($signalement, $coordonneesBailleurOldRequest);
        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les coordonnées du bailleur ont bien été modifiées.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = [
            [
                'target' => '#signalement-information-bailleur-container',
                'content' => $this->renderView('back/signalement/view/information/information-bailleur.html.twig', ['signalement' => $signalement]),
            ],
        ];
        $htmlTargetContents[] = ['target' => '#list-suivis', 'content' => $this->renderView('back/signalement/view/suivis.html.twig', ['signalement' => $signalement])];
        $functions = [['name' => 'applyFilter']];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents, 'functions' => $functions]);
    }

    #[Route('/{uuid:signalement}/edit-informations-bailleur', name: 'back_signalement_edit_informations_bailleur', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editInformationsBailleur(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        // TODO à la suppression de FEATURE_ORIENTATION : supprimer ce contrôle
        if (!$this->featureOrientation) {
            throw $this->createNotFoundException();
        }
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_informations_bailleur_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var InformationsBailleurRequest $informationsBailleurRequest */
        $informationsBailleurRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            InformationsBailleurRequest::class,
            'json'
        );
        $validationGroups = ['Default'];
        if ($signalement->getProfileDeclarant()) {
            $validationGroups[] = $signalement->getProfileDeclarant()->value;
        }
        $errorMessage = FormHelper::getErrorsFromRequest(
            $validator,
            $informationsBailleurRequest,
            $validationGroups
        );

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromInformationsBailleurRequest($signalement, $informationsBailleurRequest);
        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les informations du bailleur ont bien été modifiées.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = [
            [
                'target' => '#signalement-informations-bailleur-container',
                'content' => $this->renderView('back/signalement/view/details/informations-bailleur.html.twig', ['signalement' => $signalement]),
            ],
        ];
        $htmlTargetContents[] = ['target' => '#list-suivis', 'content' => $this->renderView('back/signalement/view/suivis.html.twig', ['signalement' => $signalement])];
        $functions = [['name' => 'applyFilter']];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents, 'functions' => $functions]);
    }

    #[Route('/{uuid:signalement}/edit-coordonnees-agence', name: 'back_signalement_edit_coordonnees_agence', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editCoordonneesAgence(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_coordonnees_agence_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }

        /** @var CoordonneesAgenceRequest $coordonneesAgenceRequest */
        $coordonneesAgenceRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            CoordonneesAgenceRequest::class,
            'json'
        );
        $validationGroups = ['Default'];
        if ($signalement->getProfileDeclarant()) {
            $validationGroups[] = $signalement->getProfileDeclarant()->value;
        }
        $errorMessage = FormHelper::getErrorsFromRequest(
            $validator,
            $coordonneesAgenceRequest,
            $validationGroups
        );

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromCoordonneesAgenceRequest($signalement, $coordonneesAgenceRequest);
        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les coordonnées de l\'agence ont bien été modifiées.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        // TODO à la suppression de FEATURE_ORIENTATION : ne garder que la cible du nouvel onglet
        if ($this->featureOrientation) {
            $htmlTargetContents = [
                [
                    'target' => '#signalement-coordonnees-agence-container',
                    'content' => $this->renderView('back/signalement/view/details/coordonnees-agence.html.twig', ['signalement' => $signalement]),
                ],
            ];
        } else {
            $htmlTargetContents = [
                [
                    'target' => '#signalement-information-agence-container',
                    'content' => $this->renderView('back/signalement/view/information/information-agence.html.twig', ['signalement' => $signalement]),
                ],
            ];
        }
        $htmlTargetContents[] = ['target' => '#list-suivis', 'content' => $this->renderView('back/signalement/view/suivis.html.twig', ['signalement' => $signalement])];
        $functions = [['name' => 'applyFilter']];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents, 'functions' => $functions]);
    }

    #[Route('/{uuid:signalement}/edit-coordonnees-syndic', name: 'back_signalement_edit_coordonnees_syndic', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editCoordonneesSyndic(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_coordonnees_syndic_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }

        /** @var CoordonneesSyndicRequest $coordonneesSyndicRequest */
        $coordonneesSyndicRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            CoordonneesSyndicRequest::class,
            'json'
        );
        $validationGroups = ['Default'];
        if ($signalement->getProfileDeclarant()) {
            $validationGroups[] = $signalement->getProfileDeclarant()->value;
        }
        $errorMessage = FormHelper::getErrorsFromRequest(
            $validator,
            $coordonneesSyndicRequest,
            $validationGroups
        );

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromCoordonneesSyndicRequest($signalement, $coordonneesSyndicRequest);
        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les coordonnées du syndic ont bien été modifiées.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        // TODO à la suppression de FEATURE_ORIENTATION : ne garder que la cible du nouvel onglet
        if ($this->featureOrientation) {
            $htmlTargetContents = [
                [
                    'target' => '#signalement-coordonnees-syndic-container',
                    'content' => $this->renderView('back/signalement/view/details/coordonnees-syndic.html.twig', ['signalement' => $signalement]),
                ],
            ];
        } else {
            $htmlTargetContents = [
                [
                    'target' => '#signalement-information-syndic-container',
                    'content' => $this->renderView('back/signalement/view/information/information-syndic.html.twig', ['signalement' => $signalement]),
                ],
            ];
        }
        $htmlTargetContents[] = ['target' => '#list-suivis', 'content' => $this->renderView('back/signalement/view/suivis.html.twig', ['signalement' => $signalement])];
        $functions = [['name' => 'applyFilter']];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents, 'functions' => $functions]);
    }

    #[Route('/{uuid:signalement}/edit-occupation-logement', name: 'back_signalement_edit_occupation_logement', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editOccupationLogement(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        SignalementQualificationNde $signalementQualificationNdeService,
    ): JsonResponse {
        // TODO à la suppression de FEATURE_ORIENTATION : supprimer ce contrôle
        if (!$this->featureOrientation) {
            throw $this->createNotFoundException();
        }
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_occupation_logement_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var OccupationLogementRequest $occupationLogementRequest */
        $occupationLogementRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            OccupationLogementRequest::class,
            'json'
        );
        $validationGroups = ['Default'];
        $validationGroups[] = $signalement->isV2() ? $signalement->getProfileDeclarant()->value : 'EDIT_'.$signalement->getProfileDeclarant()->value;

        $errorMessage = FormHelper::getErrorsFromRequest(
            $validator,
            $occupationLogementRequest,
            $validationGroups
        );

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromOccupationLogementRequest($signalement, $occupationLogementRequest);
        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les informations du logement ont bien été modifiées.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = [
            [
                'target' => '#signalement-occupation-logement-container',
                'content' => $this->renderView('back/signalement/view/details/occupation-logement.html.twig', ['signalement' => $signalement]),
            ],
        ];
        // on met à jour le panel consommation énergétique qui affiche également la date d'entrée dans le logement
        [$signalementQualificationNDE, $signalementQualificationNDECriticites] = $signalementQualificationNdeService->getSignalementQualificationNdeAndCriticites($signalement);
        $htmlTargetContents[] = [
            'target' => '#signalement-consommation-energetique-container',
            'content' => $this->renderView('back/signalement/view/details/consommation-energetique.html.twig', [
                'signalement' => $signalement,
                'signalementQualificationNDE' => $signalementQualificationNDE,
                'signalementQualificationNDECriticite' => $signalementQualificationNDECriticites,
            ]),
        ];
        // on met à jour le champ date d'entrée du panel d'édition de la consommation énergétique
        $htmlTargetContents[] = [
            'target' => '#signalement-edit-consommation-energetique-date-entree-container',
            'content' => $this->renderBlockView(
                'back/signalement/view/panels/_panel-edit-consommation-energetique.html.twig',
                'date_entree_field',
                ['signalement' => $signalement]
            ),
        ];
        // on met à jour la pré-évaluation automatique (score et qualifications recalculés) de l'onglet orientation
        $htmlTargetContents[] = [
            'target' => '#signalement-pre-evaluation-container',
            'content' => $this->renderView('back/signalement/view/orientation/pre-evaluation.html.twig', ['signalement' => $signalement]),
        ];
        $htmlTargetContents[] = ['target' => '#list-suivis', 'content' => $this->renderView('back/signalement/view/suivis.html.twig', ['signalement' => $signalement])];
        $functions = [['name' => 'applyFilter']];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents, 'functions' => $functions]);
    }

    // TODO à la suppression de FEATURE_ORIENTATION :
    // - supprimer cette route, son DTO InformationsLogementRequest et la méthode SignalementManager::updateFromInformationsLogementRequest
    // - supprimer le panel _panel-edit-informations-logement.html.twig et les tests associés
    #[Route('/{uuid:signalement}/edit-informations-logement', name: 'back_signalement_edit_informations_logement', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editInformationsLogement(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_informations_logement_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var InformationsLogementRequest $informationsLogementRequest */
        $informationsLogementRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            InformationsLogementRequest::class,
            'json'
        );
        $validationGroups = ['Default'];
        $validationGroups[] = $signalement->isV2() ? $signalement->getProfileDeclarant()->value : 'EDIT_'.$signalement->getProfileDeclarant()->value;

        $errorMessage = FormHelper::getErrorsFromRequest(
            $validator,
            $informationsLogementRequest,
            $validationGroups
        );

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromInformationsLogementRequest($signalement, $informationsLogementRequest);
        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les informations du logement ont bien été modifiées.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = [
            [
                'target' => '#signalement-information-logement-container',
                'content' => $this->renderView('back/signalement/view/information/information-logement.html.twig', ['signalement' => $signalement]),
            ],
        ];
        $htmlTargetContents[] = ['target' => '#list-suivis', 'content' => $this->renderView('back/signalement/view/suivis.html.twig', ['signalement' => $signalement])];
        $functions = [['name' => 'applyFilter']];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents, 'functions' => $functions]);
    }

    // TODO à la suppression de FEATURE_ORIENTATION : renommer en description-logement (route, méthode, DTO CompositionLogementRequest,
    // SignalementManager::updateFromCompositionLogementRequest, panel _panel-edit-composition-logement.html.twig et tests)
    // (non renommé tant que le panel est partagé avec l'ancien onglet Situation)
    #[Route('/{uuid:signalement}/edit-composition-logement', name: 'back_signalement_edit_composition_logement', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editCompositionLogement(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SignalementDraftRequestSerializer $serializer,
        ValidatorInterface $validator,
        SignalementAddressContentService $signalementAddressContentService,
        SignalementQualificationNde $signalementQualificationNdeService,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_composition_logement_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var CompositionLogementRequest $compositionLogementRequest */
        $compositionLogementRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            CompositionLogementRequest::class,
            'json'
        );

        $validationGroups = ['Default'];
        $validationGroups[] = $signalement->isV2() ? $signalement->getProfileDeclarant()->value : 'EDIT_'.$signalement->getProfileDeclarant()->value;

        $errorMessage = FormHelper::getErrorsFromRequest(
            $validator,
            $compositionLogementRequest,
            $validationGroups
        );
        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromCompositionLogementRequest($signalement, $compositionLogementRequest);
        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'La description du logement a bien été modifiée.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = $signalementAddressContentService->getHtmlTargetContentsForSignalementAddress($signalement);
        // TODO à la suppression de FEATURE_ORIENTATION : ne garder que la cible du nouvel onglet
        if ($this->featureOrientation) {
            $htmlTargetContents[] = [
                'target' => '#signalement-description-logement-container',
                'content' => $this->renderView('back/signalement/view/details/description-logement.html.twig', ['signalement' => $signalement]),
            ];
            // on met à jour le bloc occupation du logement qui affiche les désordres des autres occupants pour un appartement
            $htmlTargetContents[] = [
                'target' => '#signalement-occupation-logement-container',
                'content' => $this->renderView('back/signalement/view/details/occupation-logement.html.twig', ['signalement' => $signalement]),
            ];
            // on met à jour le bloc consommation énergétique dont le calcul dépend de la superficie du logement
            [$signalementQualificationNDE, $signalementQualificationNDECriticites] = $signalementQualificationNdeService->getSignalementQualificationNdeAndCriticites($signalement);
            $htmlTargetContents[] = [
                'target' => '#signalement-consommation-energetique-container',
                'content' => $this->renderView('back/signalement/view/details/consommation-energetique.html.twig', [
                    'signalement' => $signalement,
                    'signalementQualificationNDE' => $signalementQualificationNDE,
                    'signalementQualificationNDECriticite' => $signalementQualificationNDECriticites,
                ]),
            ];
            // on met à jour le champ superficie du panel d'édition de la consommation énergétique
            $htmlTargetContents[] = [
                'target' => '#signalement-edit-consommation-energetique-superficie-container',
                'content' => $this->renderBlockView(
                    'back/signalement/view/panels/_panel-edit-consommation-energetique.html.twig',
                    'superficie_field',
                    ['signalement' => $signalement]
                ),
            ];
            // on met à jour le champ autres occupants du panel d'édition de l'occupation du logement (affiché pour un appartement)
            $htmlTargetContents[] = [
                'target' => '#autresOccupantsDesordre-container',
                'content' => $this->renderBlockView(
                    'back/signalement/view/panels/_panel-edit-occupation-logement.html.twig',
                    'autres_occupants_desordre_field',
                    ['signalement' => $signalement]
                ),
            ];
            // on met à jour la pré-évaluation automatique (score et qualifications recalculés) de l'onglet orientation
            $htmlTargetContents[] = [
                'target' => '#signalement-pre-evaluation-container',
                'content' => $this->renderView('back/signalement/view/orientation/pre-evaluation.html.twig', ['signalement' => $signalement]),
            ];
        } else {
            $htmlTargetContents[] = [
                'target' => '#signalement-information-composition-container',
                'content' => $this->renderView('back/signalement/view/information/information-composition.html.twig', ['signalement' => $signalement]),
            ];
        }
        $htmlTargetContents[] = [
            'target' => '#signalement-edit-address-etage-container',
            'content' => $this->renderView('back/signalement/view/panels/_panel-edit-composition-logement-etage.html.twig', ['signalement' => $signalement]),
        ];

        $htmlTargetContents[] = ['target' => '#list-suivis', 'content' => $this->renderView('back/signalement/view/suivis.html.twig', ['signalement' => $signalement])];
        $functions = [['name' => 'applyFilter']];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents, 'functions' => $functions]);
    }

    /**
     * @throws \DateMalformedStringException
     */
    #[Route('/{uuid:signalement}/edit-situation-foyer', name: 'back_signalement_edit_situation_foyer', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editSituationFoyer(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        // TODO à la suppression de FEATURE_ORIENTATION : supprimer ce contrôle
        if (!$this->featureOrientation) {
            throw $this->createNotFoundException();
        }
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_situation_foyer_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var SituationFoyerRequest $situationFoyerRequest */
        $situationFoyerRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            SituationFoyerRequest::class,
            'json'
        );

        $validationGroups = ['Default'];
        $validationGroups[] = $signalement->isV2() ? $signalement->getProfileDeclarant()->value : 'EDIT_'.$signalement->getProfileDeclarant()->value;

        $errorMessage = FormHelper::getErrorsFromRequest($validator, $situationFoyerRequest, $validationGroups);

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromSituationFoyerRequest($signalement, $situationFoyerRequest);
        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'La situation du foyer a bien été modifiée.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = [
            [
                'target' => '#signalement-situation-foyer-container',
                'content' => $this->renderView('back/signalement/view/details/situation-foyer.html.twig', ['signalement' => $signalement]),
            ],
        ];
        // on met à jour le bloc démarches de l'usager dont le numéro de réclamation dépend du logement social
        $htmlTargetContents[] = [
            'target' => '#signalement-demarches-usager-container',
            'content' => $this->renderView('back/signalement/view/details/demarches-usager.html.twig', ['signalement' => $signalement]),
        ];
        // on met à jour la pré-évaluation automatique (score et qualifications recalculés) de l'onglet orientation
        $htmlTargetContents[] = [
            'target' => '#signalement-pre-evaluation-container',
            'content' => $this->renderView('back/signalement/view/orientation/pre-evaluation.html.twig', ['signalement' => $signalement]),
        ];
        $htmlTargetContents[] = ['target' => '#list-suivis', 'content' => $this->renderView('back/signalement/view/suivis.html.twig', ['signalement' => $signalement])];
        $functions = [['name' => 'applyFilter']];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents, 'functions' => $functions]);
    }

    // TODO à la suppression de FEATURE_ORIENTATION :
    // - supprimer cette route, son DTO SituationFoyerOldRequest et la méthode SignalementManager::updateFromSituationFoyerOldRequest
    // - supprimer le panel _panel-edit-situation-foyer-old.html.twig et les tests associés
    #[Route('/{uuid:signalement}/edit-situation-foyer-old', name: 'back_signalement_edit_situation_foyer_old', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editSituationFoyerOld(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_situation_foyer_old_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var SituationFoyerOldRequest $situationFoyerOldRequest */
        $situationFoyerOldRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            SituationFoyerOldRequest::class,
            'json'
        );

        $validationGroups = ['Default'];
        $validationGroups[] = $signalement->isV2() ? $signalement->getProfileDeclarant()->value : 'EDIT_'.$signalement->getProfileDeclarant()->value;

        $errorMessage = FormHelper::getErrorsFromRequest($validator, $situationFoyerOldRequest, $validationGroups);

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromSituationFoyerOldRequest($signalement, $situationFoyerOldRequest);
        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'La situation du foyer a bien été modifiée.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = [
            [
                'target' => '#signalement-information-situation-foyer-container',
                'content' => $this->renderView('back/signalement/view/information/information-situation-foyer.html.twig', ['signalement' => $signalement]),
            ],
        ];
        $htmlTargetContents[] = ['target' => '#list-suivis', 'content' => $this->renderView('back/signalement/view/suivis.html.twig', ['signalement' => $signalement])];
        $functions = [['name' => 'applyFilter']];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents, 'functions' => $functions]);
    }

    #[Route('/{uuid:signalement}/edit-procedure-demarches', name: 'back_signalement_edit_procedure_demarches', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editProcedureDemarches(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        // TODO à la suppression de FEATURE_ORIENTATION : supprimer ce contrôle
        if (!$this->featureOrientation) {
            throw $this->createNotFoundException();
        }
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_procedure_demarches_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var ProcedureDemarchesRequest $procedureDemarchesRequest */
        $procedureDemarchesRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            ProcedureDemarchesRequest::class,
            'json'
        );
        $validationGroups = ['Default'];
        $validationGroups[] = $signalement->isV2() ? $signalement->getProfileDeclarant()->value : 'EDIT_'.$signalement->getProfileDeclarant()->value;

        $errorMessage = FormHelper::getErrorsFromRequest($validator, $procedureDemarchesRequest, $validationGroups);

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromProcedureDemarchesRequest($signalement, $procedureDemarchesRequest);
        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les procédures et démarches ont bien été modifiées.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = [
            [
                'target' => '#signalement-demarches-usager-container',
                'content' => $this->renderView('back/signalement/view/details/demarches-usager.html.twig', ['signalement' => $signalement]),
            ],
        ];
        // on met à jour la pré-évaluation automatique (score et qualifications recalculés) de l'onglet orientation
        $htmlTargetContents[] = [
            'target' => '#signalement-pre-evaluation-container',
            'content' => $this->renderView('back/signalement/view/orientation/pre-evaluation.html.twig', ['signalement' => $signalement]),
        ];
        $htmlTargetContents[] = ['target' => '#list-suivis', 'content' => $this->renderView('back/signalement/view/suivis.html.twig', ['signalement' => $signalement])];
        $functions = [['name' => 'applyFilter']];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents, 'functions' => $functions]);
    }

    // TODO à la suppression de FEATURE_ORIENTATION :
    // - supprimer cette route, son DTO ProcedureDemarchesOldRequest et la méthode SignalementManager::updateFromProcedureDemarchesOldRequest
    // - supprimer le panel _panel-edit-procedure-demarches-old.html.twig et les tests associés
    #[Route('/{uuid:signalement}/edit-procedure-demarches-old', name: 'back_signalement_edit_procedure_demarches_old', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editProcedureDemarchesOld(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_procedure_demarches_old_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var ProcedureDemarchesOldRequest $procedureDemarchesOldRequest */
        $procedureDemarchesOldRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            ProcedureDemarchesOldRequest::class,
            'json'
        );
        $validationGroups = ['Default'];
        $validationGroups[] = $signalement->isV2() ? $signalement->getProfileDeclarant()->value : 'EDIT_'.$signalement->getProfileDeclarant()->value;

        $errorMessage = FormHelper::getErrorsFromRequest($validator, $procedureDemarchesOldRequest, $validationGroups);

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromProcedureDemarchesOldRequest($signalement, $procedureDemarchesOldRequest);
        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les procédures et démarches ont bien été modifiées.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        $htmlTargetContents = [
            [
                'target' => '#signalement-information-procedure-container',
                'content' => $this->renderView('back/signalement/view/information/information-procedure.html.twig', ['signalement' => $signalement]),
            ],
        ];
        $htmlTargetContents[] = ['target' => '#list-suivis', 'content' => $this->renderView('back/signalement/view/suivis.html.twig', ['signalement' => $signalement])];
        $functions = [['name' => 'applyFilter']];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents, 'functions' => $functions]);
    }

    #[Route('/{uuid:signalement}/edit-logement-vacant', name: 'back_signalement_edit_logement_vacant', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_SWITCH_LOGEMENT_VACANT, subject: 'signalement')]
    public function editLogementVacant(
        Signalement $signalement,
        Request $request,
        EntityManagerInterface $entityManager,
        SuiviDelayedFactory $suiviDelayedFactory,
    ): Response {
        $token = is_scalar($request->request->get('_token')) ? (string) $request->request->get('_token') : '';
        $logementVacant = (bool) $request->request->get('logementVacant');
        if ($this->isCsrfTokenValid('signalement_switch_logement_vacant', $token)) {
            if ($logementVacant !== $signalement->getIsLogementVacant()) {
                $signalement->setIsLogementVacant($logementVacant);
                /** @var User $user */
                $user = $this->getUser();
                $description = $logementVacant ? 'Le logement a été marqué comme vacant.' : 'Le logement a été marqué comme occupé.';
                $suiviDelayed = $suiviDelayedFactory->createSuiviDelayed(
                    user: $user,
                    signalement: $signalement,
                    type: SuiviDelayedType::BO_EDIT_OCCUPATION_LOGEMENT,
                    category: SuiviCategory::SIGNALEMENT_EDITED_BO,
                    customChanges: ['description' => $description]
                );
                $entityManager->persist($suiviDelayed);
                $entityManager->flush();
            }
            $this->addFlash('success', ['title' => 'Modifications enregistrées', 'message' => 'Le statut d\'occupation du logement a bien été modifié.']);
        } else {
            $this->addFlash('alert', ['title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF]);
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid:signalement}/edit-consommation-energetique', name: 'back_signalement_edit_consommation_energetique', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function editConsommationEnergertique(
        Signalement $signalement,
        Request $request,
        SignalementManager $signalementManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        SignalementQualificationNde $signalementQualificationNdeService,
    ): JsonResponse {
        // TODO à la suppression de FEATURE_ORIENTATION : supprimer ce contrôle
        if (!$this->featureOrientation) {
            throw $this->createNotFoundException();
        }
        /** @var array<string, mixed> $payload */
        $payload = $request->getPayload()->all();
        $token = is_scalar($payload['_token']) ? (string) $payload['_token'] : '';
        if (!$this->isCsrfTokenValid('signalement_edit_consommation_energetique_'.$signalement->getId(), $token)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var ConsommationEnergetiqueRequest $consommationEnergetiqueRequest */
        $consommationEnergetiqueRequest = $serializer->deserialize(
            json_encode($request->getPayload()->all()),
            ConsommationEnergetiqueRequest::class,
            'json'
        );
        $validationGroups = ['Default'];
        $validationGroups[] = $signalement->isV2() ? $signalement->getProfileDeclarant()->value : 'EDIT_'.$signalement->getProfileDeclarant()->value;

        $errorMessage = FormHelper::getErrorsFromRequest(
            $validator,
            $consommationEnergetiqueRequest,
            $validationGroups
        );

        if (!empty($errorMessage)) {
            $response = ['code' => Response::HTTP_BAD_REQUEST];
            $response = [...$response, ...$errorMessage];

            return $this->json($response, $response['code']);
        }
        $subscriptionCreated = $signalementManager->updateFromConsommationEnergetiqueRequest($signalement, $consommationEnergetiqueRequest);
        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'La consommation énergétique a été modifiée.'];
        if ($subscriptionCreated) {
            $flashMessages[] = ['type' => 'success', 'title' => 'Abonnement au dossier', 'message' => User::MSG_SUBSCRIPTION_CREATED];
        }
        [$signalementQualificationNDE, $signalementQualificationNDECriticites] = $signalementQualificationNdeService->getSignalementQualificationNdeAndCriticites($signalement);
        $htmlTargetContents = [
            [
                'target' => '#signalement-consommation-energetique-container',
                'content' => $this->renderView('back/signalement/view/details/consommation-energetique.html.twig', [
                    'signalement' => $signalement,
                    'signalementQualificationNDE' => $signalementQualificationNDE,
                    'signalementQualificationNDECriticite' => $signalementQualificationNDECriticites,
                ]),
            ],
        ];

        // on met à jour le panel occupation-logement qui affiche également la date d'entrée dans le logement
        $htmlTargetContents[] = [
            'target' => '#signalement-occupation-logement-container',
            'content' => $this->renderView('back/signalement/view/details/occupation-logement.html.twig', [
                'signalement' => $signalement,
            ]),
        ];

        // on met à jour le panel description-logement qui affiche également la superficie du logement
        $htmlTargetContents[] = [
            'target' => '#signalement-description-logement-container',
            'content' => $this->renderView('back/signalement/view/details/description-logement.html.twig', [
                'signalement' => $signalement,
            ]),
        ];
        // on met à jour le champ date d'entrée du panel d'édition de l'occupation du logement
        $htmlTargetContents[] = [
            'target' => '#occupationLogementDateEntree-container',
            'content' => $this->renderBlockView(
                'back/signalement/view/panels/_panel-edit-occupation-logement.html.twig',
                'date_entree_field',
                ['signalement' => $signalement]
            ),
        ];
        // on met à jour le champ superficie du panel d'édition de la description du logement
        $htmlTargetContents[] = [
            'target' => '#compositionLogementSuperficie-container',
            'content' => $this->renderBlockView(
                'back/signalement/view/panels/_panel-edit-composition-logement.html.twig',
                'superficie_field',
                ['signalement' => $signalement]
            ),
        ];
        // on met à jour la pré-évaluation automatique (score et qualifications recalculés) de l'onglet orientation
        $htmlTargetContents[] = [
            'target' => '#signalement-pre-evaluation-container',
            'content' => $this->renderView('back/signalement/view/orientation/pre-evaluation.html.twig', ['signalement' => $signalement]),
        ];
        $htmlTargetContents[] = [
            'target' => '#list-suivis',
            'content' => $this->renderView('back/signalement/view/suivis.html.twig', ['signalement' => $signalement]),
        ];

        $functions = [['name' => 'applyFilter']];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents, 'functions' => $functions]);
    }
}
