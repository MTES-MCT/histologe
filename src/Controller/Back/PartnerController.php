<?php

namespace App\Controller\Back;

use App\Entity\Enum\PartnerType as EnumPartnerType;
use App\Entity\Partner;
use App\Entity\User;
use App\Form\PartnerType;
use App\Manager\PartnerManager;
use App\Manager\UserManager;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/partenaires')]
class PartnerController extends AbstractController
{
    public const DEFAULT_TERRITORY_AIN = 1;

    #[Route('/', name: 'back_partner_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        PartnerRepository $partnerRepository,
        TerritoryRepository $territoryRepository,
        ParameterBagInterface $parameterBag
    ): Response {
        $this->denyAccessUnlessGranted('PARTNER_LIST', null);
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
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('PARTNER_CREATE', null);
        $partner = new Partner();
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(PartnerType::class, $partner, [
            'can_edit_territory' => $user->isSuperAdmin(),
            'territory' => $user->getTerritory(),
            'route' => 'back_partner_new',
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Si la personne identifiée n'est pas super admin (donc qu'elle ne peut pas éditer),
            // on redéfinit le territoire avec celui de l'utilisateur en cours
            if (!$user->isSuperAdmin()) {
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
        Request $request,
        Partner $partner,
        PartnerRepository $partnerRepository,
    ): Response {
        $this->denyAccessUnlessGranted('PARTNER_EDIT', $partner);
        if ($partner->getIsArchive()) {
            $this->addFlash('error', 'Ce partenaire est archivé.');

            return $this->redirect($this->generateUrl('back_partner_index', [
                'page' => 1,
                'territory' => $partner->getTerritory()->getId(),
            ]));
        }

        return $this->renderForm('back/partner/view.html.twig', [
            'partner' => $partner,
            'partners' => $partnerRepository->findAllList($partner->getTerritory()),
        ]);
    }

    #[Route('/{id}/editer', name: 'back_partner_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Partner $partner,
        EntityManagerInterface $entityManager,
        PartnerRepository $partnerRepository,
    ): Response {
        $this->denyAccessUnlessGranted('PARTNER_EDIT', $partner);
        if ($partner->getIsArchive()) {
            return $this->redirect($this->generateUrl('back_partner_index', [
                'page' => 1,
                'territory' => $partner->getTerritory()->getId(),
            ]));
        }
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(PartnerType::class, $partner, [
            'can_edit_territory' => $user->isSuperAdmin(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Le partenaire a bien été modifié.');

            return $this->redirectToRoute('back_partner_view', [
                'id' => $partner->getId(),
            ]);
        }

        $this->displayErrors($form);

        return $this->renderForm('back/partner/edit.html.twig', [
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
        EntityManagerInterface $entityManager
    ): Response {
        $partnerId = $request->request->get('partner_id');
        /** @var Partner $partner */
        $partner = $partnerManager->find($partnerId);
        $this->denyAccessUnlessGranted('PARTNER_DELETE', $partner);
        if ($partner
            && $this->isCsrfTokenValid('partner_delete', $request->request->get('_token'))
        ) {
            $partner->setIsArchive(true);
            foreach ($partner->getUsers() as $user) {
                $user->setStatut(User::STATUS_ARCHIVE);
                $entityManager->persist($user);
            }
            $entityManager->persist($partner);
            $entityManager->flush();
            $this->addFlash('success', 'Le partenaire a bien été supprimé.');

            return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
        }
        $this->addFlash('error', 'Une erreur est survenue lors de la suppression...');

        return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/ajoututilisateur', name: 'back_partner_user_add', methods: ['POST'])]
    public function addUser(
        Request $request,
        Partner $partner,
        UserManager $userManager,
    ): Response {
        $this->denyAccessUnlessGranted('USER_CREATE', $this->getUser());
        if (
            $this->isCsrfTokenValid('partner_user_create', $request->request->get('_token'))
            && $data = $request->get('user_create')
        ) {
            $user = $userManager->createUserFromData($partner, $data);
            $message = 'L\'utilisateur a bien été créé. Un email de confirmation a été envoyé à '.$user->getEmail();
            $this->addFlash('success', $message);

            return $this->redirectToRoute('back_partner_view', ['id' => $partner->getId()], Response::HTTP_SEE_OTHER);
        }
        $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout d\'utilisateur.');

        return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/editerutilisateur', name: 'back_partner_user_edit', methods: ['POST'])]
    public function editUser(
        Request $request,
        UserManager $userManager,
    ): Response {
        $this->denyAccessUnlessGranted('USER_EDIT', $this->getUser());
        if (
            $this->isCsrfTokenValid('partner_user_edit', $request->request->get('_token'))
            && $userId = $request->request->get('user_id')
        ) {
            /** @var User $user */
            $user = $userManager->find((int) $userId);
            $data = $request->get('user_edit');
            $user = $userManager->updateUserFromData($user, $data);
            $partnerId = $user->getPartner()->getId();

            $message = 'L\'utilisateur a bien été modifié.';
            $this->addFlash('success', $message);

            return $this->redirectToRoute('back_partner_view', ['id' => $partnerId], Response::HTTP_SEE_OTHER);
        }
        $this->addFlash('error', 'Une erreur est survenue lors de l\'édition de l\'utilisateur.');

        return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/transfererutilisateur', name: 'back_partner_user_transfer', methods: ['POST'])]
    public function transferUser(Request $request, UserManager $userManager, PartnerManager $partnerManager): Response
    {
        $this->denyAccessUnlessGranted('USER_TRANSFER', $this->getUser());
        if (
            $this->isCsrfTokenValid('partner_user_transfer', $request->request->get('_token'))
            && $data = $request->get('user_transfer')
        ) {
            $partner = $partnerManager->find($data['partner']);
            $user = $userManager->find($data['user']);
            $userManager->transferUserToPartner($user, $partner);
            $this->addFlash('success', 'L\'utilisateur a bien été transféré.');

            return $this->redirectToRoute('back_partner_view', ['id' => $partner->getId()], Response::HTTP_SEE_OTHER);
        }
        $this->addFlash('error', 'Une erreur est survenue lors du transfert...');

        return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/supprimerutilisateur', name: 'back_partner_user_delete', methods: ['POST'])]
    public function deleteUser(
        Request $request,
        UserManager $userManager,
        NotificationMailerRegistry $notificationMailerRegistry
    ): Response {
        $this->denyAccessUnlessGranted('USER_DELETE', $this->getUser());
        if (
            $this->isCsrfTokenValid('partner_user_delete', $request->request->get('_token'))
            && $userId = $request->request->get('user_id')
        ) {
            /** @var User $user */
            $user = $userManager->find($userId);
            $user->setStatut(User::STATUS_ARCHIVE);
            $userManager->save($user);
            $notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_ACCOUNT_DELETE,
                    to: $user->getEmail(),
                    territory: $user->getTerritory()
                )
            );
            $this->addFlash('success', 'L\'utilisateur a bien été supprimé.');

            return $this->redirectToRoute(
                'back_partner_view',
                ['id' => $user->getPartner()->getId()],
                Response::HTTP_SEE_OTHER
            );
        }
        $this->addFlash('error', 'Une erreur est survenue lors de la suppression...');

        return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/checkmail', name: 'back_partner_check_user_email', methods: ['POST'])]
    public function checkMail(
        Request $request,
        UserRepository $userRepository
    ): Response {
        $this->denyAccessUnlessGranted('USER_CHECKMAIL', $this->getUser());
        if (
            $this->isCsrfTokenValid('partner_checkmail', $request->request->get('_token'))
            && $userRepository->findOneBy(['email' => $request->get('email')])
        ) {
            return $this->json(['error' => 'email_exist'], Response::HTTP_BAD_REQUEST);
        }

        $validator = new EmailValidator();
        $emailValid = $validator->isValid($request->get('email'), new RFCValidation());

        if (!$emailValid) {
            return $this->json(['error' => 'email_unvalid'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => 'email_ok']);
    }

    private function displayErrors(FormInterface $form): void
    {
        /** @var FormError $error */
        foreach ($form->getErrors(true) as $error) {
            $this->addFlash('error', $error->getMessage());
        }
    }
}
