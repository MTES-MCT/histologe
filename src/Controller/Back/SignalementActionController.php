<?php

namespace App\Controller\Back;

use App\Dto\AgentSelection;
use App\Dto\RefusSignalement;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\SuiviFile;
use App\Entity\Tag;
use App\Entity\User;
use App\Form\AddSuiviType;
use App\Form\AgentSelectionType;
use App\Form\RefusSignalementType;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\UserSignalementSubscriptionManager;
use App\Repository\AffectationRepository;
use App\Repository\SuiviRepository;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use App\Security\Voter\SignalementVoter;
use App\Security\Voter\SuiviVoter;
use App\Service\FormHelper;
use App\Service\Gouv\Rnb\RnbService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/signalements')]
class SignalementActionController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'EDITION_SUIVI_ENABLE')]
        private readonly bool $editionSuiviEnable,
        #[Autowire(env: 'DELAY_SUIVI_EDITABLE_IN_MINUTES')]
        private readonly int $delaySuiviEditableInMinutes,
    ) {
    }

    #[Route('/{uuid:signalement}/accept', name: 'back_signalement_accept_post', methods: 'POST')]
    public function validationResponseSignalementPost(
        Signalement $signalement,
        Request $request,
        UserSignalementSubscriptionManager $userSignalementSubscriptionManager,
        SuiviManager $suiviManager,
    ): Response {
        $this->denyAccessUnlessGranted(SignalementVoter::SIGN_VALIDATE, $signalement);
        /** @var User $user */
        $user = $this->getUser();
        $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory());

        $agentSelection = (new AgentSelection())->setSignalement($signalement);
        $form = $this->createForm(AgentSelectionType::class, $agentSelection, [
            'only_rt' => true,
            'label' => 'Sélectionnez le(s) responsable(s) de territoire à abonner au dossier',
        ]);
        $form->handleRequest($request);
        if (!$form->isSubmitted()) {
            return $this->json(['code' => Response::HTTP_BAD_REQUEST]);
        }
        if (!$form->isValid()) {
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm(form: $form, withPrefix: true)];

            return $this->json($response, $response['code']);
        }
        foreach ($agentSelection->getAgents() as $agent) {
            $userSignalementSubscriptionManager->createOrGet($agent, $signalement, $user);
        }
        $signalement->setValidatedAt(new \DateTimeImmutable());
        $signalement->setStatut(SignalementStatus::ACTIVE);
        $suiviManager->createSuivi(
            signalement: $signalement,
            description: Suivi::DESCRIPTION_SIGNALEMENT_VALIDE,
            type : Suivi::TYPE_AUTO,
            category: SuiviCategory::SIGNALEMENT_IS_ACTIVE,
            partner: $partner,
            user : $user,
            isPublic: true,
            context: Suivi::CONTEXT_SIGNALEMENT_ACCEPTED,
            createSubscription: false
        );
        $this->addFlash('success', 'Signalement accepté avec succès !');

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid:signalement}/accept', name: 'back_signalement_accept', methods: 'GET')]
    public function validationResponseSignalement(
        Signalement $signalement,
        Request $request,
        SuiviManager $suiviManager,
        UserRepository $userRepository,
    ): Response {
        $this->denyAccessUnlessGranted(SignalementVoter::SIGN_VALIDATE, $signalement);
        /** @var User $user */
        $user = $this->getUser();
        $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory());
        if ($this->isGranted('ROLE_ADMIN') || (count($userRepository->findActiveTerritoryAdminsInPartner($partner)) > 1)) {
            $this->addFlash('error', 'Vous devez sélectionner les responsables de territoire à abonner au dossier.');

            return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
        }
        if ($this->isCsrfTokenValid('signalement_validation_response_'.$signalement->getId(), (string) $request->get('_token'))) {
            $signalement->setValidatedAt(new \DateTimeImmutable());
            $signalement->setStatut(SignalementStatus::ACTIVE);
            $subscriptionCreated = false;
            $suiviManager->createSuivi(
                signalement: $signalement,
                description: Suivi::DESCRIPTION_SIGNALEMENT_VALIDE,
                type : Suivi::TYPE_AUTO,
                category: SuiviCategory::SIGNALEMENT_IS_ACTIVE,
                partner: $partner,
                user : $user,
                isPublic: true,
                context: Suivi::CONTEXT_SIGNALEMENT_ACCEPTED,
                subscriptionCreated: $subscriptionCreated,
            );
            $this->addFlash('success', 'Signalement accepté avec succès !');
            if ($subscriptionCreated) {
                $this->addFlash('success', User::MSG_SUBSCRIPTION_CREATED);
            }
        } else {
            $this->addFlash('error', 'Une erreur est survenue...');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid:signalement}/deny', name: 'back_signalement_deny', methods: 'POST')]
    public function validationResponseDenySignalement(
        Signalement $signalement,
        Request $request,
        SuiviManager $suiviManager,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(SignalementVoter::SIGN_VALIDATE, $signalement);
        $refusSignalement = (new RefusSignalement())->setSignalement($signalement);
        $refusSignalementRoute = $this->generateUrl('back_signalement_deny', ['uuid' => $signalement->getUuid()]);
        $form = $this->createForm(RefusSignalementType::class, $refusSignalement, ['action' => $refusSignalementRoute]);
        $form->handleRequest($request);
        if (!$form->isSubmitted()) {
            return $this->json(['code' => Response::HTTP_BAD_REQUEST]);
        }
        if (!$form->isValid()) {
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm(form: $form, withPrefix: true)];

            return $this->json($response, $response['code']);
        }
        $signalement->setMotifRefus($refusSignalement->getMotifRefus());
        $description = 'Signalement cloturé car non-valide avec le motif suivant : '.$refusSignalement->getMotifRefus()->label().'<br>Plus précisément :<br>'.$refusSignalement->getDescription();

        /** @var User $user */
        $user = $this->getUser();
        $signalement->setStatut(SignalementStatus::REFUSED);
        $subscriptionCreated = false;
        $suiviManager->createSuivi(
            signalement: $signalement,
            description: $description,
            type : Suivi::TYPE_AUTO,
            category: SuiviCategory::SIGNALEMENT_IS_REFUSED,
            partner: $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory()),
            user : $user,
            isPublic: true,
            context: Suivi::CONTEXT_SIGNALEMENT_REFUSED,
            files: $refusSignalement->getFiles(),
            subscriptionCreated: $subscriptionCreated,
        );
        $this->addFlash('success', 'Signalement refusé avec succès !');
        if ($subscriptionCreated) {
            $this->addFlash('success', User::MSG_SUBSCRIPTION_CREATED);
        }

        $url = $this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json(['redirect' => true, 'url' => $url]);
    }

    #[Route('/{uuid:signalement}/suivi/add', name: 'back_signalement_add_suivi', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_CREATE_SUIVI, subject: 'signalement')]
    public function addSuiviSignalement(
        Signalement $signalement,
        Request $request,
        SuiviManager $suiviManager,
        LoggerInterface $logger,
    ): JsonResponse {
        $suivi = (new Suivi())->setSignalement($signalement);
        $addSuiviRoute = $this->generateUrl('back_signalement_add_suivi', ['uuid' => $signalement->getUuid()]);
        $form = $this->createForm(AddSuiviType::class, $suivi, ['action' => $addSuiviRoute]);
        $form->handleRequest($request);
        if (!$form->isSubmitted()) {
            return $this->json(['code' => Response::HTTP_BAD_REQUEST]);
        }
        if (!$form->isValid()) {
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm(form: $form, withPrefix: true)];

            return $this->json($response, $response['code']);
        }

        try {
            /** @var User $user */
            $user = $this->getUser();
            $subscriptionCreated = false;
            $suiviManager->createSuivi(
                signalement: $signalement,
                description: $suivi->getDescription(),
                type: Suivi::TYPE_PARTNER,
                category: SuiviCategory::MESSAGE_PARTNER,
                partner: $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory()),
                user: $user,
                isPublic: $suivi->getIsPublic(),
                files: $form->get('files')->getData(),
                subscriptionCreated: $subscriptionCreated,
            );
        } catch (\Throwable $exception) {
            $logger->error($exception->getMessage());
            $errors = ['main' => ['errors' => ['Une erreur est survenue lors de la publication, veuillez réessayer.']]];
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => $errors];

            return $this->json($response, $response['code']);
        }
        $response = ['code' => Response::HTTP_OK];
        $this->addFlash('success', 'Suivi publié avec succès !');
        if ($subscriptionCreated) {
            $this->addFlash('success', User::MSG_SUBSCRIPTION_CREATED);
        }

        return $this->json($response, $response['code']);
    }

    #[Route('/{uuid:signalement}/suivi/delete', name: 'back_signalement_delete_suivi', methods: 'POST')]
    public function deleteSuivi(
        Request $request,
        Signalement $signalement,
        SuiviRepository $suiviRepository,
        ManagerRegistry $doctrine,
    ): JsonResponse {
        $suivi = $suiviRepository->findOneBy(['id' => $request->get('suivi')]);
        $this->denyAccessUnlessGranted(SuiviVoter::SUIVI_DELETE, $suivi);
        if ($this->isCsrfTokenValid('signalement_delete_suivi_'.$signalement->getId(), (string) $request->get('_token'))) {
            $limit = new \DateTimeImmutable('-'.$this->delaySuiviEditableInMinutes.' minutes');
            if ($suivi->getCreatedAt() > $limit && $this->editionSuiviEnable) {
                $doctrine->getManager()->remove($suivi);
            } else {
                /** @var User $user */
                $user = $this->getUser();
                $suivi->setDeletedAt(new \DateTimeImmutable());
                $suivi->setDeletedBy($user);
            }
            $doctrine->getManager()->flush();
            $this->addFlash('success', 'Le suivi a été supprimé.');
        } else {
            $this->addFlash('error', 'Le jeton CSRF est invalide. Veuillez réessayer.');
        }

        return $this->json(['redirect' => true, 'url' => $this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()])]);
    }

    #[Route('/suivi/{suivi}/edit', name: 'back_signalement_edit_suivi', methods: ['GET', 'POST'])]
    public function editSuivi(
        Request $request,
        Suivi $suivi,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(SuiviVoter::SUIVI_EDIT, $suivi);
        $suivi->setSuiviTransformerService(null);
        $form = $this->createForm(AddSuiviType::class, $suivi, ['action' => $this->generateUrl('back_signalement_edit_suivi', ['suivi' => $suivi->getId()])]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm(form: $form, withPrefix: true)];

                return $this->json($response, $response['code']);
            }
            foreach ($suivi->getSuiviFiles() as $suiviFile) {
                $entityManager->remove($suiviFile);
            }
            $entityManager->flush();
            foreach ($form->get('files')->getData() as $file) {
                $suiviFile = (new SuiviFile())->setFile($file)->setSuivi($suivi)->setTitle($file->getTitle());
                $entityManager->persist($suiviFile);
            }
            $entityManager->flush();

            $response = ['code' => Response::HTTP_OK];
            $this->addFlash('success', 'Le suivi a été modifié avec succès !');

            return $this->json($response, $response['code']);
        }

        $html = $this->renderView('back/signalement/view/add-edit-suivi-form.html.twig', [
            'form' => $form,
            'formId' => 'fr-modal-edit-suivi-form',
            'signalement' => $suivi->getSignalement(),
        ]);

        return $this->json(['content' => $html]);
    }

    #[Route('/{uuid:signalement}/reopen', name: 'back_signalement_reopen')]
    public function reopenSignalement(
        Signalement $signalement,
        Request $request,
        AffectationRepository $affectationRepository,
        SuiviManager $suiviManager,
    ): RedirectResponse|JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        if ($this->isCsrfTokenValid('signalement_reopen_'.$signalement->getId(), (string) $request->get('_token')) && $response = $request->get('signalement-action')) {
            if ($this->isGranted('ROLE_ADMIN_TERRITORY') && isset($response['reopenAll'])) {
                $affectationRepository->updateStatusBySignalement(AffectationStatus::WAIT, $signalement);
                $reopenFor = 'tous les partenaires';
            } elseif (!$this->isGranted('ROLE_ADMIN_TERRITORY') && SignalementStatus::CLOSED === $signalement->getStatut()) {
                $this->addFlash('error', 'Seul un responsable de territoire peut réouvrir un signalement fermé !');

                return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
            } else {
                $partner = $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory());
                $reopenFor = mb_strtoupper($partner->getNom());
                foreach ($partner->getAffectations() as $affectation) {
                    if ($affectation->getSignalement()->getId() === $signalement->getId()) {
                        $affectation->setStatut(AffectationStatus::WAIT);
                        break;
                    }
                }
            }
            if (SignalementStatus::INJONCTION_BAILLEUR !== $signalement->getStatut()) {
                $signalement->setStatut(SignalementStatus::ACTIVE);
            }
            $subscriptionCreated = false;
            $suiviManager->createSuivi(
                signalement: $signalement,
                description: 'Signalement rouvert pour '.$reopenFor,
                type: Suivi::TYPE_AUTO,
                category: SuiviCategory::SIGNALEMENT_IS_REOPENED,
                partner: $user->getPartnerInTerritoryOrFirstOne($signalement->getTerritory()),
                user: $user,
                isPublic: '1' === $request->get('publicSuivi'),
                subscriptionCreated: $subscriptionCreated,
            );
            $this->addFlash('success', 'Signalement rouvert avec succès !');
            if ($subscriptionCreated) {
                $this->addFlash('success', User::MSG_SUBSCRIPTION_CREATED);
            }
        } else {
            $this->addFlash('error', 'Erreur lors de la réouverture du signalement !');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid:signalement}/switch', name: 'back_signalement_switch_value', methods: 'POST')]
    #[IsGranted(SignalementVoter::SIGN_EDIT_ACTIVE, subject: 'signalement')]
    public function switchValue(Signalement $signalement, Request $request, EntityManagerInterface $entityManager): RedirectResponse|JsonResponse
    {
        if ($this->isCsrfTokenValid('signalement_switch_value_'.$signalement->getUuid(), (string) $request->get('_token'))) {
            $value = $request->get('value');

            $tag = $entityManager->getRepository(Tag::class)->find((int) $value);
            if ($signalement->getTags()->contains($tag)) {
                $signalement->removeTag($tag);
            } else {
                $signalement->addTag($tag);
            }

            $entityManager->persist($signalement);
            $entityManager->flush();

            return $this->json(['response' => 'success']);
        }

        return $this->json(['response' => 'error'], 400);
    }

    #[Route('/{uuid:signalement}/set-rnb', name: 'back_signalement_set_rnb', methods: 'POST')]
    public function setRnbId(
        Signalement $signalement,
        Request $request,
        RnbService $rnbService,
        SignalementManager $signalementManager,
    ): RedirectResponse {
        if (!$this->isGranted(SignalementVoter::SIGN_EDIT_ACTIVE, $signalement) && !$this->isGranted(SignalementVoter::SIGN_EDIT_NEED_VALIDATION, $signalement)) {
            throw $this->createAccessDeniedException();
        }
        $rnbId = $request->get('rnbId');
        $token = $request->get('_token');
        if (!$this->isCsrfTokenValid('signalement_set_rnb_'.$signalement->getUuid(), $token)) {
            $this->addFlash('error', 'Le jeton CSRF est invalide. Veuillez réessayer.');

            return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
        }
        if (!empty($signalement->getGeoloc())) {
            $this->addFlash('error', 'Le signalement a déjà une géolocalisation.');

            return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
        }
        $building = $rnbService->getBuilding($rnbId);
        if (!$building) {
            $this->addFlash('error', 'Le bâtiment n\'a pas été trouvé.');
        } else {
            $signalement->setRnbIdOccupant($building->getRnbId());
            $signalement->setGeoloc(['lat' => $building->getLat(), 'lng' => $building->getLng()]);
            $signalementManager->flush();
            $this->addFlash('success', 'Le bâtiment a été mis à jour avec succès.');
        }

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid:signalement}/subscribe', name: 'back_signalement_subscribe_list', methods: 'POST')]
    public function subscribeList(
        Signalement $signalement,
        UserSignalementSubscriptionRepository $signalementSubscriptionRepository,
        UserSignalementSubscriptionManager $signalementSubscriptionManager,
        EntityManagerInterface $entityManager,
        Request $request,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(SignalementVoter::SIGN_SUBSCRIBE, $signalement);
        /** @var User $user */
        $user = $this->getUser();
        $subscription = $signalementSubscriptionRepository->findOneBy(['signalement' => $signalement, 'user' => $user]);
        if (!$subscription) {
            throw $this->createAccessDeniedException();
        }
        $agentSelection = (new AgentSelection())->setSignalement($signalement);
        $agentsSubscriptionForm = $this->createForm(AgentSelectionType::class, $agentSelection);
        $agentsSubscriptionForm->handleRequest($request);
        if ($agentsSubscriptionForm->isSubmitted() && $agentsSubscriptionForm->isValid()) {
            foreach ($agentSelection->getAgents() as $agent) {
                $signalementSubscriptionManager->createOrGet($agent, $signalement, $user);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Les agents ont bien été abonnés au dossier.');

            return $this->json(['redirect' => true, 'url' => $this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()])]);
        }
        if (!$agentsSubscriptionForm->isSubmitted()) {
            return $this->json(['code' => Response::HTTP_BAD_REQUEST]);
        }
        $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm(form: $agentsSubscriptionForm, withPrefix: true)];

        return $this->json($response, $response['code']);
    }

    #[Route('/{uuid:signalement}/subscribe', name: 'back_signalement_subscribe', methods: 'GET')]
    public function subscribe(
        Signalement $signalement,
        UserSignalementSubscriptionManager $signalementSubscriptionManager,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted(SignalementVoter::SIGN_SUBSCRIBE, $signalement);
        $token = $request->get('_token');
        if (!$this->isCsrfTokenValid('subscribe', $token)) {
            $this->addFlash('error', 'Le jeton CSRF est invalide. Veuillez réessayer.');

            return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
        }

        /** @var User $user */
        $user = $this->getUser();

        $signalementSubscriptionManager->createOrGet($user, $signalement, $user);
        $signalementSubscriptionManager->flush();

        $msg = 'Vous avez rejoint le dossier, vous apparaissez maintenant dans la liste des agents abonnés au dossier.
        Le dossier apparaît dans vos dossiers sur votre tableau de bord et vous recevrez les mises à jour du dossier.';
        $this->addFlash('success', $msg);

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    #[Route('/{uuid:signalement}/unsubscribe', name: 'back_signalement_unsubscribe', methods: ['GET', 'POST'])]
    public function unsubscribe(
        Signalement $signalement,
        UserSignalementSubscriptionManager $signalementSubscriptionManager,
        UserSignalementSubscriptionRepository $signalementSubscriptionRepository,
        AffectationRepository $affectationRepository,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted(SignalementVoter::SIGN_SUBSCRIBE, $signalement);
        $successMsg = 'Vous avez quitté le dossier, vous n\'apparaissez plus dans la liste des agents abonnés au dossier et vous ne recevrez plus les mises à jour du dossier.';

        /** @var User $user */
        $user = $this->getUser();
        $partner = $user->getPartnerInTerritory($signalement->getTerritory());

        // If no partner in territory, no point in blocking unsubscription
        if ($partner) {
            $affectation = $affectationRepository->findOneBy(['partner' => $partner, 'signalement' => $signalement]);

            // If no affectation for user partner, no point in blocking unsubscription
            if ($affectation) {
                // If partner and affectation exist, check if user is alone in partner for this signalement to avoid unsubscription
                $subscriptionsInMyPartner = $signalementSubscriptionRepository->findForSignalementAndPartner($signalement, $partner);
                if (\count($subscriptionsInMyPartner) < 2 && !$user->isAloneInPartner($partner)) {
                    if (null === $request->get('agents_selection')) {
                        $this->addFlash('error', 'Vous êtes le seul agent de votre partenaire sur ce dossier. Si vous souhaitez quitter le dossier, vous devez d\'abord transférer le dossier à un autre agent de votre partenaire.');

                        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
                    }
                    $agentsSelection = (new AgentSelection())->setSignalement($signalement);
                    $agentsSelectionFormRoute = $this->generateUrl('back_signalement_unsubscribe', ['uuid' => $signalement->getUuid()]);
                    $form = $this->createForm(
                        AgentSelectionType::class,
                        $agentsSelection,
                        [
                            'action' => $agentsSelectionFormRoute,
                            'exclude_user' => $this->getUser(),
                            'label' => 'Sélectionnez le(s) agent(s) à qui transmettre le dossier',
                        ]
                    );
                    $form->handleRequest($request);

                    if (!$form->isSubmitted()) {
                        return $this->json(['code' => Response::HTTP_BAD_REQUEST]);
                    }
                    if (!$form->isValid()) {
                        $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => FormHelper::getErrorsFromForm(form: $form, withPrefix: true)];

                        return $this->json($response, $response['code']);
                    }

                    $this->unsubscribeUser($user, $signalement, $signalementSubscriptionManager, $signalementSubscriptionRepository);
                    foreach ($agentsSelection->getAgents() as $agent) {
                        $signalementSubscriptionManager->createOrGet($agent, $signalement, $user, $affectation);
                        $signalementSubscriptionManager->flush();
                    }
                    $this->addFlash('success', $successMsg);

                    $url = $this->generateUrl('back_signalement_view', ['uuid' => $signalement->getUuid()], UrlGeneratorInterface::ABSOLUTE_URL);

                    return $this->json(['redirect' => true, 'url' => $url]);
                }

                if ($user->isAloneInPartner($partner)) {
                    $this->addFlash('error', 'Vous ne pouvez pas quitter un dossier étant seul agent de votre partenaire.');

                    return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
                }
            }
        }

        $token = $request->get('_token');
        if (!$this->isCsrfTokenValid('unsubscribe', $token)) {
            $this->addFlash('error', 'Le jeton CSRF est invalide. Veuillez réessayer.');

            return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
        }

        $this->unsubscribeUser($user, $signalement, $signalementSubscriptionManager, $signalementSubscriptionRepository);

        $this->addFlash('success', $successMsg);

        return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
    }

    private function unsubscribeUser(User $user, Signalement $signalement, UserSignalementSubscriptionManager $manager, UserSignalementSubscriptionRepository $repo): void
    {
        $subscription = $repo->findOneBy(['user' => $user, 'signalement' => $signalement]);
        if ($subscription) {
            $manager->remove($subscription);
        }
    }
}
