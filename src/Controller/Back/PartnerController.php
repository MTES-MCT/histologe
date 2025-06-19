<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\PartnerType as EnumPartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Enum\UserStatus;
use App\Entity\Intervention;
use App\Entity\Partner;
use App\Entity\User;
use App\Entity\UserPartner;
use App\Form\PartnerPerimetreType;
use App\Form\PartnerType;
use App\Form\SearchPartnerType;
use App\Form\UserPartnerEmailType;
use App\Form\UserPartnerType;
use App\Manager\AffectationManager;
use App\Manager\InterventionManager;
use App\Manager\PartnerManager;
use App\Manager\PopNotificationManager;
use App\Manager\UserManager;
use App\Repository\AutoAffectationRuleRepository;
use App\Repository\JobEventRepository;
use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
use App\Service\ListFilters\SearchPartner;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Sanitizer;
use App\Service\Signalement\VisiteNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/bo/partenaires')]
class PartnerController extends AbstractController
{
    #[Route('/', name: 'back_partner_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN_TERRITORY')]
    public function index(
        Request $request,
        PartnerRepository $partnerRepository,
        ParameterBagInterface $parameterBag,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $searchPartner = new SearchPartner($user);
        $form = $this->createForm(SearchPartnerType::class, $searchPartner);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchPartner = new SearchPartner($user);
        }
        $maxListPagination = $parameterBag->get('standard_max_list_pagination');
        $paginatedPartners = $partnerRepository->findFilteredPaginated($searchPartner, $maxListPagination);

        return $this->render('back/partner/index.html.twig', [
            'form' => $form,
            'searchPartner' => $searchPartner,
            'partners' => $paginatedPartners,
            'pages' => (int) ceil($paginatedPartners->count() / $maxListPagination),
        ]);
    }

    #[Route('/ajout', name: 'back_partner_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN_TERRITORY')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $partner = new Partner();
        /** @var User $user */
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN')) {
            $partner->setTerritory($user->getFirstTerritory());
        }
        $form = $this->createForm(PartnerType::class, $partner);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($partner);
            $entityManager->flush();
            $this->addFlash('success', 'Le partenaire a bien été créé.');

            return $this->redirectToRoute('back_partner_view', [
                'id' => $partner->getId(),
            ]);
        }

        $this->displayErrors($form);

        return $this->render('back/partner/edit.html.twig', [
            'partner' => $partner,
            'form' => $form,
            'create' => true,
        ]);
    }

    #[Route('/{id}/voir', name: 'back_partner_view', methods: ['GET', 'POST'])]
    public function view(
        Partner $partner,
        PartnerRepository $partnerRepository,
        JobEventRepository $jobEventRepository,
        AutoAffectationRuleRepository $autoAffectationRuleRepository,
    ): Response {
        $this->denyAccessUnlessGranted('PARTNER_EDIT', $partner);
        if ($partner->getIsArchive()) {
            $this->addFlash('error', 'Ce partenaire est archivé.');

            return $this->redirect($this->generateUrl('back_partner_index', [
                'page' => 1,
                'territory' => $partner->getTerritory()->getId(),
            ]));
        }

        $lastJobEvent = $jobEventRepository->findLastEsaboraJobByPartner($partner);
        $lastJobEventDate = $lastJobEvent && !empty($lastJobEvent['last_event']) ? new \DateTimeImmutable($lastJobEvent['last_event']) : null;

        $partnerAutoAffectationRules = null;
        if ($this->isGranted('ROLE_ADMIN')) {
            $partnerAutoAffectationRules = $autoAffectationRuleRepository->findForPartner($partner);
        }

        return $this->render('back/partner/view.html.twig', [
            'partner' => $partner,
            'partners' => $partnerRepository->findAllList($partner->getTerritory()),
            'last_job_date' => $lastJobEventDate,
            'partnerAutoAffectationRules' => $partnerAutoAffectationRules,
        ]);
    }

    #[Route('/{id}/editer', name: 'back_partner_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Partner $partner,
        EntityManagerInterface $entityManager,
        PartnerRepository $partnerRepository,
        NotificationMailerRegistry $notificationMailerRegistry,
        VisiteNotifier $visiteNotifier,
        WorkflowInterface $interventionPlanningStateMachine,
        InterventionManager $interventionManager,
    ): Response {
        $this->denyAccessUnlessGranted('PARTNER_EDIT', $partner);
        if ($partner->getIsArchive()) {
            return $this->redirect($this->generateUrl('back_partner_index', [
                'page' => 1,
                'territory' => $partner->getTerritory()->getId(),
            ]));
        }

        $previousTerritory = $partner->getTerritory();

        $form = $this->createForm(PartnerType::class, $partner);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!\in_array($partner->getType(), [EnumPartnerType::ARS, EnumPartnerType::COMMUNE_SCHS])) {
                $partner->setIsEsaboraActive(false);
            }
            if (!\in_array($partner->getType(), [EnumPartnerType::COMMUNE_SCHS])) {
                $partner->setIsIdossActive(false);
            }

            if ($partner->getTerritory() != $previousTerritory) {
                /** @var User $partnerUser */
                foreach ($partner->getUsers() as $partnerUser) {
                    $notificationMailerRegistry->send(
                        new NotificationMail(
                            type: NotificationMailerType::TYPE_ACCOUNT_TRANSFER,
                            to: $partnerUser->getEmail(),
                            territory: $partner->getTerritory(),
                            user: $partnerUser,
                            params: ['partner_name' => $partner->getNom()]
                        )
                    );
                }
                // delete affectations "en attente" et "acceptées"
                $affectations = $partner->getAffectations();
                foreach ($affectations as $affectation) {
                    if (
                        AffectationStatus::ACCEPTED === $affectation->getStatut()
                        || AffectationStatus::WAIT === $affectation->getStatut()
                    ) {
                        $partner->removeAffectation($affectation);
                    }
                }

                $this->cancelOrReplanVisites(
                    partner: $partner,
                    visiteNotifier: $visiteNotifier,
                    interventionPlanningStateMachine: $interventionPlanningStateMachine,
                    interventionManager: $interventionManager,
                );
            }

            $entityManager->flush();
            $this->addFlash('success', 'Le partenaire a bien été modifié.');

            return $this->redirectToRoute('back_partner_view', [
                'id' => $partner->getId(),
            ]);
        }

        $this->displayErrors($form);

        return $this->render('back/partner/edit.html.twig', [
            'partner' => $partner,
            'partners' => $partnerRepository->findAllList($partner->getTerritory()),
            'form' => $form,
            'create' => false,
        ]);
    }

    #[Route('/{id}/edit-perimetre', name: 'back_partner_edit_perimetre', methods: ['GET', 'POST'])]
    public function editPerimetre(
        Request $request,
        Partner $partner,
        PartnerManager $partnerManager,
    ): Response {
        $this->denyAccessUnlessGranted('PARTNER_EDIT', $partner);
        if ($partner->getIsArchive()) {
            return $this->redirect($this->generateUrl('back_partner_index', ['page' => 1, 'territory' => $partner->getTerritory()->getId()]));
        }
        $form = $this->createForm(PartnerPerimetreType::class, $partner);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $partnerManager->save($partner);
            $this->addFlash('success', 'Le périmètre a bien été modifié.');

            return $this->redirectToRoute('back_partner_view', ['id' => $partner->getId(), '_fragment' => 'perimetre']);
        }

        return $this->render('back/partner/edit-perimetre.html.twig', [
            'partner' => $partner,
            'form' => $form,
        ]);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/supprimer', name: 'back_partner_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        PartnerManager $partnerManager,
        EntityManagerInterface $entityManager,
        NotificationMailerRegistry $notificationMailerRegistry,
        VisiteNotifier $visiteNotifier,
        WorkflowInterface $interventionPlanningStateMachine,
        InterventionManager $interventionManager,
        AffectationManager $affectationManager,
        PopNotificationManager $popNotificationManager,
    ): Response {
        $partnerId = $request->request->get('partner_id');
        /** @var ?Partner $partner */
        $partner = $partnerManager->find($partnerId);
        $this->denyAccessUnlessGranted('PARTNER_DELETE', $partner);
        if (
            $partner
            && $this->isCsrfTokenValid('partner_delete', $request->request->get('_token'))
        ) {
            if (null !== $partner->getEmail()) {
                $partner->setEmail(Sanitizer::tagArchivedEmail($partner->getEmail()));
            }
            $partner->setIsArchive(true);
            foreach ($partner->getUsers() as $user) {
                if ($user->getUserPartners()->count() > 1) {
                    foreach ($user->getUserPartners() as $userPartner) {
                        if ($userPartner->getPartner()->getId() === $partner->getId()) {
                            $popNotificationManager->createOrUpdatePopNotification($user, 'removePartner', $partner);
                            $entityManager->remove($userPartner);
                            break;
                        }
                    }
                } else {
                    $user->setEmail(Sanitizer::tagArchivedEmail($user->getEmail()));
                    $user->setStatut(UserStatus::ARCHIVE);
                    $entityManager->persist($user);
                    $notificationMailerRegistry->send(
                        new NotificationMail(
                            type: NotificationMailerType::TYPE_ACCOUNT_DELETE,
                            to: $user->getEmail(),
                            territory: $partner->getTerritory()
                        )
                    );
                }
            }

            // delete affectations "en attente" et "acceptées"
            $affectationManager->deleteAffectationsByPartner($partner);

            $this->cancelOrReplanVisites(
                partner: $partner,
                visiteNotifier: $visiteNotifier,
                interventionPlanningStateMachine: $interventionPlanningStateMachine,
                interventionManager: $interventionManager,
            );

            $entityManager->persist($partner);
            $entityManager->flush();
            $this->addFlash('success', 'Le partenaire a bien été supprimé.');

            return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
        }
        $this->addFlash('error', 'Une erreur est survenue lors de la suppression...');

        return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
    }

    private function cancelOrReplanVisites(
        Partner $partner,
        VisiteNotifier $visiteNotifier,
        WorkflowInterface $interventionPlanningStateMachine,
        InterventionManager $interventionManager,
    ): void {
        if (\in_array(Qualification::VISITES, $partner->getCompetence())) {
            /** @var Intervention $intervention */
            foreach ($partner->getInterventions() as $intervention) {
                if (
                    InterventionType::VISITE == $intervention->getType()
                    && Intervention::STATUS_PLANNED == $intervention->getStatus()
                ) {
                    if ($this->shouldCancelFutureVisite($intervention)) {
                        $interventionPlanningStateMachine->apply($intervention, 'cancel');
                        $interventionManager->save($intervention);

                    // planned visites in the past are un-assigned
                    } else {
                        $intervention->setPartner(null);
                        $interventionManager->save($intervention);

                        $visiteNotifier->notifyVisiteToConclude($intervention);
                    }
                }
            }
        }
    }

    #[Route('/{id}/add-user-multi', name: 'back_partner_add_user_multi', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN_PARTNER')]
    public function addUserPartnerMulti(
        Request $request,
        Partner $partner,
        UserRepository $userRepository,
        UserManager $userManager,
        NotificationMailerRegistry $notificationMailerRegistry,
        PopNotificationManager $popNotificationManager,
    ): JsonResponse|RedirectResponse {
        $this->denyAccessUnlessGranted('USER_CREATE', $partner);
        $userTmp = new User();
        $userPartner = (new UserPartner())->setUser($userTmp)->setPartner($partner);
        $userTmp->addUserPartner($userPartner);
        $checkMailRoute = $this->generateUrl('back_partner_add_user_email', ['id' => $partner->getId()]);
        $formMultiMail = $this->createForm(UserPartnerEmailType::class, $userTmp, ['action' => $checkMailRoute]);
        $formMultiMail->handleRequest($request);
        if ($formMultiMail->isSubmitted() && $formMultiMail->isValid()) {
            $user = $userRepository->findAgentByEmail($userTmp->getEmail());
            if ($user) {
                $userPartner->setUser($user);
                $popNotificationManager->createOrUpdatePopNotification($user, 'addPartner', $partner);
                $userManager->save($userPartner);
                $notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_ACCOUNT_NEW_TERRITORY,
                        to: $user->getEmail(),
                        user: $user,
                        territory: $partner->getTerritory(),
                        params: ['partner_name' => $partner->getNom()]
                    )
                );
                $message = 'L\'utilisateur a bien été ajouté à votre partenaire. Un e-mail de confirmation a été envoyé à '.$user->getEmail();
                $this->addFlash('success', $message);

                $url = $this->generateUrl('back_partner_view', ['id' => $partner->getId(), '_fragment' => 'agents'], UrlGeneratorInterface::ABSOLUTE_URL);

                return $this->json(['redirect' => true, 'url' => $url]);
            }
            $formMultiMail->get('email')->addError(new FormError('Agent introuvable avec cette adresse e-mail.'));
        }
        $content = $this->renderView('_partials/_modal_user_create_email.html.twig', ['formCheckMail' => $formMultiMail]);

        return $this->json(['content' => $content, 'title' => 'Ajouter un utilisateur']);
    }

    #[Route('/{id}/add-user', name: 'back_partner_add_user', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN_PARTNER')]
    public function addUserPartner(
        Request $request,
        Partner $partner,
        UserManager $userManager,
    ): JsonResponse|RedirectResponse {
        $this->denyAccessUnlessGranted('USER_CREATE', $partner);
        $user = new User();
        $userPartner = (new UserPartner())->setUser($user)->setPartner($partner);
        $user->addUserPartner($userPartner);
        $addUserRoute = $this->generateUrl('back_partner_add_user', ['id' => $partner->getId()]);
        $formUserPartner = $this->createForm(UserPartnerType::class, $user, ['action' => $addUserRoute]);
        $formUserPartner->handleRequest($request);
        if ($formUserPartner->isSubmitted() && $formUserPartner->isValid()) {
            $userExist = $userManager->findOneBy(['email' => $user->getEmail()]);
            if ($userExist && !\in_array('ROLE_USAGER', $userExist->getRoles())) {
                $addUserOnPartnerRoute = $this->generateUrl('back_partner_add_user_multi', ['id' => $partner->getId()]);
                $formMultiMail = $this->createForm(UserPartnerEmailType::class, $user, ['action' => $addUserOnPartnerRoute]);
                $content = $this->renderView('_partials/_modal_user_create_multi.html.twig', ['formMultiMail' => $formMultiMail, 'user' => $userExist, 'partner' => $partner]);

                return $this->json(['content' => $content, 'title' => 'Compte existant sur un autre territoire', 'submitLabel' => 'Ajouter l\'utilisateur']);
            }
            $user->setRoles([$formUserPartner->get('role')->getData()]);
            if (null === $user->getIsMailingSummary()) {
                $user->setIsMailingSummary(true);
            }
            if ($userExist) {
                $userExist->setNom($user->getNom());
                $userExist->setPrenom($user->getPrenom());
                $userExist->setIsMailingActive($user->getIsMailingActive());
                $userExist->setIsMailingSummary($user->getIsMailingSummary());
                $userExist->setHasPermissionAffectation($user->hasPermissionAffectation());
                $userExist->setStatut(UserStatus::INACTIVE);
                $userExist->setRoles([$formUserPartner->get('role')->getData()]);
                $userPartner->setUser($userExist);
                $user = $userExist;
                $userManager->sendAccountActivationNotification($user);
            }
            $userManager->persist($userPartner);
            $userManager->save($user);
            $message = 'L\'utilisateur a bien été créé. Un e-mail de confirmation a été envoyé à '.$user->getEmail();
            $this->addFlash('success', $message);

            $url = $this->generateUrl('back_partner_view', ['id' => $partner->getId(), '_fragment' => 'agents'], UrlGeneratorInterface::ABSOLUTE_URL);

            return $this->json(['redirect' => true, 'url' => $url]);
        }
        $content = $this->renderView('_partials/_modal_user_create_form.html.twig', ['formUserPartner' => $formUserPartner]);

        return $this->json(['content' => $content, 'title' => 'Ajouter un utilisateur']);
    }

    #[Route('/{partner}/editerutilisateur/{user}', name: 'back_partner_user_edit')]
    public function editUser(
        Partner $partner,
        User $user,
        Request $request,
        UserManager $userManager,
    ): JsonResponse|RedirectResponse {
        $this->denyAccessUnlessGranted('USER_EDIT', $user);
        $originalEmail = $user->getEmail();
        $editUserRoute = $this->generateUrl('back_partner_user_edit', ['partner' => $partner->getId(), 'user' => $user->getId(), 'from' => $request->query->get('from')]);
        $formDisabled = false;
        if (1 !== $user->getUserPartners()->count()) {
            $formDisabled = true;
        }
        $formUserPartner = $this->createForm(UserPartnerType::class, $user, [
            'action' => $editUserRoute,
            'validation_groups' => ['user_partner_mail', 'user_partner'],
            'disabled' => $formDisabled]
        );
        $formUserPartner->handleRequest($request);
        if ($formUserPartner->isSubmitted() && $formUserPartner->isValid()) {
            if ($formUserPartner->isValid()) { // @phpstan-ignore-line
                if ($originalEmail != $user->getEmail()) {
                    $user->setPassword('');
                    $userManager->sendAccountActivationNotification($user);
                }
                $user->setRoles([$formUserPartner->get('role')->getData()]);
                $userManager->flush();
                $this->addFlash('success', 'L\'utilisateur a bien été modifié.');
                $url = $this->generateUrl('back_partner_view', ['id' => $partner->getId(), '_fragment' => 'agents'], UrlGeneratorInterface::ABSOLUTE_URL);
                if ('users' == $request->query->get('from')) {
                    $url = $this->generateUrl('back_user_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
                }

                return $this->json(['redirect' => true, 'url' => $url]);
            }
        }
        $content = $this->renderView('_partials/_modal_user_edit_form.html.twig', ['formUserPartner' => $formUserPartner, 'user' => $user]);

        return $this->json(['content' => $content, 'title' => 'Modifier le compte de : '.$user->getEmail(), 'disabled' => $formDisabled]);
    }

    #[Route('/{id}/transfererutilisateur', name: 'back_partner_user_transfer', methods: ['POST'])]
    public function transferUser(Request $request, Partner $fromPartner, UserManager $userManager, PartnerManager $partnerManager, PartnerRepository $partnerRepository): Response
    {
        $data = $request->get('user_transfer');
        if (!$this->isCsrfTokenValid('partner_user_transfer', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide, merci d\'actualiser la page et réessayer.');

            return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
        }
        $toPartner = $partnerManager->find($data['partner']);
        $user = $userManager->find($data['user']);
        if (!$toPartner || !$user) {
            $this->addFlash('error', 'Partenaire ou utilisateur introuvable.');

            return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
        }
        $this->denyAccessUnlessGranted('USER_TRANSFER', $user);
        if (!$this->isGranted('ROLE_ADMIN')) {
            $partnersAuthorized = $partnerRepository->findAllList($fromPartner->getTerritory());
            if (!isset($partnersAuthorized[$toPartner->getId()])) {
                $this->addFlash('error', 'Vous n\'avez pas les droits pour transférer sur ce partenaire.');

                return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        $userManager->transferUserToPartner($user, $fromPartner, $toPartner);
        $this->addFlash('success', 'L\'utilisateur a bien été transféré.');

        return $this->redirectToRoute('back_partner_view', ['id' => $toPartner->getId(), '_fragment' => 'agents'], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/supprimerutilisateur', name: 'back_partner_user_delete', methods: ['POST'])]
    public function deleteUser(
        Request $request,
        Partner $partner,
        UserManager $userManager,
        NotificationMailerRegistry $notificationMailerRegistry,
        PopNotificationManager $popNotificationManager,
    ): Response {
        $userId = $request->request->get('user_id');
        if (!$this->isCsrfTokenValid('partner_user_delete', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide, merci d\'actualiser la page et réessayer.');

            return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
        }
        /** @var User $user */
        $user = $userManager->find($userId);
        if (!$user || !$user->hasPartner($partner)) {
            $this->addFlash('error', 'Utilisateur introuvable sur le partenaire.');

            return $this->redirectToRoute('back_partner_view', ['id' => $partner->getId()], Response::HTTP_SEE_OTHER);
        }

        $this->denyAccessUnlessGranted('USER_DELETE', $user);
        if (UserStatus::ARCHIVE === $user->getStatut()) {
            $this->addFlash('error', 'Cet utilisateur est déjà supprimé.');

            return $this->redirectToRoute('back_partner_view', ['id' => $partner->getId()], Response::HTTP_SEE_OTHER);
        }

        if ($user->getUserPartners()->count() > 1) {
            foreach ($user->getUserPartners() as $userPartner) {
                if ($userPartner->getPartner()->getId() === $partner->getId()) {
                    $popNotificationManager->createOrUpdatePopNotification($user, 'removePartner', $partner);
                    $userManager->remove($userPartner);
                    break;
                }
            }
            $notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_ACCOUNT_REMOVE_FROM_TERRITORY,
                    to: $user->getEmail(),
                    territory: $partner->getTerritory(),
                    params: ['partner_name' => $partner->getNom()]
                )
            );
            $this->addFlash('success', 'L\'utilisateur a bien été supprimé du partenaire.');
            $this->addFlash('warning', 'Attention, cet utilisateur est toujours actif sur d\'autres territoires.');

            return $this->redirectToRoute('back_partner_view', ['id' => $partner->getId()], Response::HTTP_SEE_OTHER);
        }
        $notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_ACCOUNT_DELETE,
                to: $user->getEmail(),
                territory: $partner->getTerritory()
            )
        );
        $user->setEmail(Sanitizer::tagArchivedEmail($user->getEmail()));
        $user->setStatut(UserStatus::ARCHIVE);
        $user->setProConnectUserId(null);
        $userManager->save($user);
        $this->addFlash('success', 'L\'utilisateur a bien été supprimé.');

        return $this->redirectToRoute('back_partner_view', ['id' => $partner->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/add-user-email', name: 'back_partner_add_user_email')]
    #[IsGranted('ROLE_ADMIN_PARTNER')]
    public function addUserEmail(
        Request $request,
        Partner $partner,
        UserRepository $userRepository,
    ): Response {
        $this->denyAccessUnlessGranted('USER_CREATE', $partner);
        $user = new User();
        $user->setIsMailingActive(true);
        $user->addUserPartner((new UserPartner())->setUser($user)->setPartner($partner));
        $checkMailRoute = $this->generateUrl('back_partner_add_user_email', ['id' => $partner->getId()]);
        $formCheckMail = $this->createForm(UserPartnerEmailType::class, $user, ['action' => $checkMailRoute]);
        $formCheckMail->handleRequest($request);
        if ($formCheckMail->isSubmitted() && $formCheckMail->isValid()) {
            $userExist = $userRepository->findAgentByEmail($user->getEmail());
            if ($userExist) {
                $addUserOnPartnerRoute = $this->generateUrl('back_partner_add_user_multi', ['id' => $partner->getId()]);
                $formMultiMail = $this->createForm(UserPartnerEmailType::class, $user, ['action' => $addUserOnPartnerRoute]);
                $content = $this->renderView('_partials/_modal_user_create_multi.html.twig', ['formMultiMail' => $formMultiMail, 'user' => $userExist, 'partner' => $partner]);

                return $this->json(['content' => $content, 'title' => 'Compte existant sur un autre territoire', 'submitLabel' => 'Ajouter l\'utilisateur']);
            }
            $addUserRoute = $this->generateUrl('back_partner_add_user', ['id' => $partner->getId()]);
            $formUserPartner = $this->createForm(UserPartnerType::class, $user, ['action' => $addUserRoute]);
            $content = $this->renderView('_partials/_modal_user_create_form.html.twig', ['formUserPartner' => $formUserPartner]);

            return $this->json(['content' => $content, 'title' => 'Ajouter un utilisateur']);
        }
        $content = $this->renderView('_partials/_modal_user_create_email.html.twig', ['formCheckMail' => $formCheckMail]);

        return $this->json(['content' => $content, 'title' => 'Ajouter un utilisateur']);
    }

    private function displayErrors(FormInterface $form): void
    {
        /** @var FormError $error */
        foreach ($form->getErrors(true) as $error) {
            $this->addFlash('error', $error->getMessage());
        }
    }

    private function shouldCancelFutureVisite(Intervention $intervention): bool
    {
        return $intervention->getScheduledAt() > new \DateTimeImmutable();
    }
}
