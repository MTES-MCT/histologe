<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\PartnerType as EnumPartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Intervention;
use App\Entity\Partner;
use App\Entity\User;
use App\Form\PartnerType;
use App\Manager\InterventionManager;
use App\Manager\PartnerManager;
use App\Manager\UserManager;
use App\Repository\JobEventRepository;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Sanitizer;
use App\Service\Signalement\VisiteNotifier;
use App\Validator\EmailFormatValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/bo/partenaires')]
class PartnerController extends AbstractController
{
    public const DEFAULT_TERRITORY_AIN = 1;

    #[Route('/', name: 'back_partner_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN_TERRITORY')]
    public function index(
        Request $request,
        PartnerRepository $partnerRepository,
        TerritoryRepository $territoryRepository,
    ): Response {
        $page = $request->get('page') ?? 1;
        /** @var User $user */
        $user = $this->getUser();
        if ($this->isGranted('ROLE_ADMIN')) {
            $currentTerritory = $territoryRepository->find((int) $request->get('territory'));
        } else {
            $currentTerritory = $user->getTerritory();
        }
        $currentType = $request->get('type');
        $enumType = $request->get('type') ? EnumPartnerType::tryFrom($request->get('type')) : null;
        $userTerms = $request->get('userTerms');

        $paginatedPartners = $partnerRepository->getPartners($currentTerritory, $enumType, $userTerms, (int) $page);

        $types = EnumPartnerType::getLabelList();

        if (Request::METHOD_POST === $request->getMethod()) {
            $currentTerritory = $territoryRepository->find((int) $request->request->get('territory'));
            $currentType = $request->request->get('type');
            $userTerms = $request->request->get('bo-filters-usersterms');

            return $this->redirect($this->generateUrl('back_partner_index', [
                'page' => 1,
                'territory' => $currentTerritory?->getId(),
                'type' => $currentType,
                'userTerms' => $userTerms,
            ]));
        }

        $totalPartners = \count($paginatedPartners);

        return $this->render('back/partner/index.html.twig', [
            'currentTerritory' => $currentTerritory,
            'territories' => $territoryRepository->findAllList(),
            'partners' => $paginatedPartners,
            'currentType' => $currentType,
            'types' => $types,
            'userTerms' => $userTerms,
            'total' => $totalPartners,
            'page' => $page,
            'pages' => (int) ceil($totalPartners / Partner::MAX_LIST_PAGINATION),
        ]);
    }

    #[Route('/ajout', name: 'back_partner_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN_TERRITORY')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $partner = new Partner();
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(PartnerType::class, $partner, [
            'can_edit_territory' => $this->isGranted('ROLE_ADMIN'),
            'territory' => $user->getTerritory(),
            'route' => 'back_partner_new',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Si la personne identifiée n'est pas super admin (donc qu'elle ne peut pas éditer),
            // on redéfinit le territoire avec celui de l'utilisateur en cours
            if (!$this->isGranted('ROLE_ADMIN')) {
                $partner->setTerritory($user->getTerritory());
            }
            $entityManager->persist($partner);
            $entityManager->flush();
            $this->addFlash('success', 'Le partenaire a bien été créé.');

            return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
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

        return $this->renderForm('back/partner/view.html.twig', [
            'partner' => $partner,
            'partners' => $partnerRepository->findAllList($partner->getTerritory()),
            'last_job_date' => $lastJobEventDate,
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

        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(PartnerType::class, $partner, [
            'can_edit_territory' => $user->isSuperAdmin(),
        ]);
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
                    $partnerUser->setTerritory($partner->getTerritory());
                    $entityManager->persist($partnerUser);
                    $notificationMailerRegistry->send(
                        new NotificationMail(
                            type: NotificationMailerType::TYPE_ACCOUNT_TRANSFER,
                            to: $partnerUser->getEmail(),
                            territory: $partner->getTerritory(),
                            user: $partnerUser,
                        )
                    );
                }
                // delete affectations "en attente" et "acceptées"
                $affectations = $partner->getAffectations();
                foreach ($affectations as $affectation) {
                    if (
                        Affectation::STATUS_ACCEPTED === $affectation->getStatut()
                        || Affectation::STATUS_WAIT === $affectation->getStatut()
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

    #[Route('/supprimer', name: 'back_partner_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        PartnerManager $partnerManager,
        EntityManagerInterface $entityManager,
        NotificationMailerRegistry $notificationMailerRegistry,
        VisiteNotifier $visiteNotifier,
        WorkflowInterface $interventionPlanningStateMachine,
        InterventionManager $interventionManager,
    ): Response {
        $partnerId = $request->request->get('partner_id');
        /** @var Partner $partner */
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
                $user->setEmail(Sanitizer::tagArchivedEmail($user->getEmail()));
                $user->setStatut(User::STATUS_ARCHIVE);
                $entityManager->persist($user);
                $notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_ACCOUNT_DELETE,
                        to: $user->getEmail(),
                        territory: $user->getTerritory()
                    )
                );
            }

            // delete affectations "en attente" et "acceptées"
            $affectations = $partner->getAffectations();
            foreach ($affectations as $affectation) {
                if (
                    Affectation::STATUS_ACCEPTED === $affectation->getStatut()
                    || Affectation::STATUS_WAIT === $affectation->getStatut()
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
    ) {
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

    #[Route('/{id}/ajoututilisateur', name: 'back_partner_user_add', methods: ['POST'])]
    public function addUser(
        Request $request,
        Partner $partner,
        UserManager $userManager,
        PartnerRepository $partnerRepository,
    ): Response {
        $this->denyAccessUnlessGranted('USER_CREATE', $partner);
        $data = $request->get('user_create');
        if (!$this->isCsrfTokenValid('partner_user_create', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide, merci d\'actualiser la page et réessayer.');

            return $this->redirectToRoute('back_partner_view', ['id' => $partner->getId()], Response::HTTP_SEE_OTHER);
        }
        if (!$this->canAttributeRole($data['roles'])) {
            return $this->redirectToRoute('back_partner_view', ['id' => $partner->getId()], Response::HTTP_SEE_OTHER);
        }
        if (!EmailFormatValidator::validate($data['email'])) {
            $this->addFlash('error', 'L\'adresse e-mail n\'est pas valide.');

            return $this->redirectToRoute('back_partner_view', ['id' => $partner->getId()], Response::HTTP_SEE_OTHER);
        }

        /** @var User $user */
        $user = $userManager->findOneBy(['email' => $data['email']]);
        $partnerExist = $partnerRepository->findOneBy(['email' => $data['email']]);

        if (null !== $partnerExist) {
            $this->addFlash('error', 'Un partenaire existe déjà avec cette adresse e-mail.');

            return $this->redirectToRoute('back_partner_view', ['id' => $partner->getId()], Response::HTTP_SEE_OTHER);
        } elseif (null !== $user && \in_array('ROLE_USAGER', $user->getRoles())) {
            $data['territory'] = $partner->getTerritory();
            $data['partner'] = $partner;
            $data['statut'] = User::STATUS_INACTIVE;
            $userManager->updateUserFromData($user, $data);
        } elseif (null !== $user) {
            $this->addFlash('error', 'Un utilisateur existe déjà avec cette adresse e-mail.');

            return $this->redirectToRoute('back_partner_view', ['id' => $partner->getId()], Response::HTTP_SEE_OTHER);
        } else {
            $user = $userManager->createUserFromData($partner, $data);
        }

        $message = 'L\'utilisateur a bien été créé. Un e-mail de confirmation a été envoyé à '.$user->getEmail();
        $this->addFlash('success', $message);

        return $this->redirectToRoute('back_partner_view', ['id' => $partner->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/editerutilisateur', name: 'back_partner_user_edit', methods: ['POST'])]
    public function editUser(
        Request $request,
        UserManager $userManager,
        UserRepository $userRepository,
        PartnerRepository $partnerRepository,
    ): Response {
        $userId = $request->request->get('user_id');
        $user = $userManager->find((int) $userId);
        /** @var User $user */
        if (!$userId || !$user || !$user->getPartner()) {
            $this->addFlash('error', 'Utilisateur introuvable.');

            return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
        }
        if (!$this->isCsrfTokenValid('partner_user_edit', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide, merci d\'actualiser la page et réessayer.');

            return $this->redirectToRoute('back_partner_view', ['id' => $user->getPartner()->getId()], Response::HTTP_SEE_OTHER);
        }
        $this->denyAccessUnlessGranted('USER_EDIT', $user);

        $data = $request->get('user_edit');
        if (!EmailFormatValidator::validate($data['email'])) {
            $this->addFlash('error', 'L\'adresse e-mail n\'est pas valide.');

            return $this->redirectToRoute('back_partner_view', ['id' => $user->getPartner()->getId()], Response::HTTP_SEE_OTHER);
        }
        if ($data['email'] != $user->getEmail()) {
            $userExist = $userRepository->findOneBy(['email' => $data['email']]);
            if ($userExist && !\in_array('ROLE_USAGER', $userExist->getRoles())) {
                $this->addFlash('error', 'Un utilisateur existe déjà avec cette adresse e-mail.');

                return $this->redirectToRoute('back_partner_view', ['id' => $user->getPartner()->getId()], Response::HTTP_SEE_OTHER);
            }
            $partnerExist = $partnerRepository->findOneBy(['email' => $data['email']]);
            if ($partnerExist) {
                $this->addFlash('error', 'Un partenaire existe déjà avec cette adresse e-mail.');

                return $this->redirectToRoute('back_partner_view', ['id' => $user->getPartner()->getId()], Response::HTTP_SEE_OTHER);
            }
        }
        if ($data['roles'] != $user->getRoles()[0]) {
            if (!$this->canAttributeRole($data['roles'])) {
                return $this->redirectToRoute('back_partner_view', ['id' => $user->getPartner()->getId()], Response::HTTP_SEE_OTHER);
            }
        }
        $user = $userManager->updateUserFromData(
            $user,
            [
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'roles' => $data['roles'],
                'email' => $data['email'],
                'isMailingActive' => $data['isMailingActive'],
            ]
        );

        $message = 'L\'utilisateur a bien été modifié.';
        $this->addFlash('success', $message);

        return $this->redirectToRoute('back_partner_view', ['id' => $user->getPartner()->getId()], Response::HTTP_SEE_OTHER);
    }

    private function canAttributeRole(string $role): bool
    {
        $authorizedRoles = ['ROLE_USER_PARTNER', 'ROLE_ADMIN_PARTNER'];
        if ($this->isGranted('ROLE_ADMIN_TERRITORY')) {
            $authorizedRoles[] = 'ROLE_ADMIN_TERRITORY';
        }
        if ($this->isGranted('ROLE_ADMIN')) {
            $authorizedRoles[] = 'ROLE_ADMIN';
        }
        if (!\in_array($role, $authorizedRoles)) {
            $this->addFlash('error', 'Vous n\'avez pas les droits pour attribuer ce rôle.');

            return false;
        }

        return true;
    }

    #[Route('/transfererutilisateur', name: 'back_partner_user_transfer', methods: ['POST'])]
    public function transferUser(Request $request, UserManager $userManager, PartnerManager $partnerManager, PartnerRepository $partnerRepository): Response
    {
        $data = $request->get('user_transfer');
        if (!$this->isCsrfTokenValid('partner_user_transfer', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide, merci d\'actualiser la page et réessayer.');

            return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
        }
        $partner = $partnerManager->find($data['partner']);
        $user = $userManager->find($data['user']);
        if (!$partner || !$user) {
            $this->addFlash('error', 'Partenaire ou utilisateur introuvable.');

            return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
        }
        $this->denyAccessUnlessGranted('USER_TRANSFER', $user);
        if (!$this->isGranted('ROLE_ADMIN')) {
            /** @var User $currentUser */
            $currentUser = $this->getUser();
            $partnersAuthorized = $partnerRepository->findAllList($currentUser->getTerritory());
            if (!isset($partnersAuthorized[$partner->getId()])) {
                $this->addFlash('error', 'Vous n\'avez pas les droits pour transférer sur ce partenaire.');

                return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        $userManager->transferUserToPartner($user, $partner);
        $this->addFlash('success', 'L\'utilisateur a bien été transféré.');

        return $this->redirectToRoute('back_partner_view', ['id' => $partner->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/supprimerutilisateur', name: 'back_partner_user_delete', methods: ['POST'])]
    public function deleteUser(
        Request $request,
        UserManager $userManager,
        NotificationMailerRegistry $notificationMailerRegistry
    ): Response {
        $userId = $request->request->get('user_id');
        if (!$this->isCsrfTokenValid('partner_user_delete', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide, merci d\'actualiser la page et réessayer.');

            return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
        }
        /** @var User $user */
        $user = $userManager->find($userId);
        $this->denyAccessUnlessGranted('USER_DELETE', $user);
        $notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_ACCOUNT_DELETE,
                to: $user->getEmail(),
                territory: $user->getTerritory()
            )
        );
        if (User::STATUS_ARCHIVE === $user->getStatut()) {
            $this->addFlash('error', 'Cet utilisateur est déjà supprimé.');
        } else {
            $user->setEmail(Sanitizer::tagArchivedEmail($user->getEmail()));
            $user->setStatut(User::STATUS_ARCHIVE);
            $userManager->save($user);
            $this->addFlash('success', 'L\'utilisateur a bien été supprimé.');
        }

        return $this->redirectToRoute(
            'back_partner_view',
            ['id' => $user->getPartner()->getId()],
            Response::HTTP_SEE_OTHER
        );
    }

    #[Route('/checkmail', name: 'back_partner_check_user_email', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN_PARTNER')]
    public function checkMail(
        Request $request,
        UserRepository $userRepository
    ): Response {
        if ($this->isCsrfTokenValid('partner_checkmail', $request->request->get('_token'))) {
            $userExist = $userRepository->findOneBy(['email' => $request->get('email')]);
            if (
                $userExist
                && $userExist->getId() !== (int) $request->get('userEditedId')
                && !\in_array('ROLE_USAGER', $userExist->getRoles())
            ) {
                return $this->json(['error' => 'Un utilisateur existe déjà avec cette adresse e-mail.'], Response::HTTP_BAD_REQUEST);
            }

            if (!EmailFormatValidator::validate($request->get('email'))) {
                return $this->json(['error' => 'L\'adresse e-mail est invalide'], Response::HTTP_BAD_REQUEST);
            }

            return $this->json(['success' => 'email_ok']);
        }

        return $this->json(['status' => 'denied'], 400);
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
