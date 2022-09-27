<?php

namespace App\Controller\Back;

use App\Entity\Partner;
use App\Entity\User;
use App\Form\PartnerType;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

#[Route('/bo/partner')]
class BackPartnerController extends AbstractController
{
    public const DEFAULT_TERRITORY_AIN = 1;

    #[Route('/', name: 'back_partner_index', methods: ['GET', 'POST'])]
    public function index(Request $request,
                          PartnerRepository $partnerRepository,
                          TerritoryRepository $territoryRepository): Response
    {
        $this->denyAccessUnlessGranted('PARTNER_LIST', null);
        $page = $request->get('page') ?? 1;

        if ($this->isGranted('ROLE_ADMIN')) {
            $territory = empty($request->get('territory')) ? self::DEFAULT_TERRITORY_AIN : (int) $request->get('territory');
            $currentTerritory = $territoryRepository->find($territory);
        } else {
            $currentTerritory = $this->getUser()->getTerritory();
        }

        $paginatedPartners = $partnerRepository->getPartners($currentTerritory, (int) $page);

        if (Request::METHOD_POST === $request->getMethod()) {
            $currentTerritory = $territoryRepository->find((int) $request->request->get('territory'));

            return $this->redirect($this->generateUrl('back_partner_index', [
                'page' => 1,
                'territory' => $currentTerritory->getId(),
            ]));
        }

        $totalPartners = \count($paginatedPartners);

        return $this->render('back/partner/index.html.twig', [
            'currentTerritory' => $currentTerritory,
            'territories' => $territoryRepository->findAllList(),
            'partners' => $paginatedPartners,
            'total' => $totalPartners,
            'page' => $page,
            'pages' => (int) ceil($totalPartners / Partner::MAX_LIST_PAGINATION),
        ]);
    }

    #[Route('/new', name: 'back_partner_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, LoginLinkHandlerInterface $loginLinkHandler, NotificationService $notificationService): Response
    {
        $this->denyAccessUnlessGranted('PARTNER_CREATE', null);
        $partner = new Partner();
        $form = $this->createForm(PartnerType::class, $partner, [
            'can_edit_territory' => $this->getUser()->isSuperAdmin(),
            'territory' => $this->getUser()->getTerritory(),
            'route' => 'back_partner_new',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si la personne identifiée n'est pas super admin (donc qu'elle ne peut pas éditer),
            // on redéfinit le territoire avec celui de l'utilisateur en cours
            if (!$this->getUser()->isSuperAdmin()) {
                $partner->setTerritory($this->getUser()->getTerritory());
            }
            self::checkFormExtraData($form, $partner, $entityManager, $loginLinkHandler, $notificationService);
            $entityManager->persist($partner);
            $entityManager->flush();

            return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('back/partner/edit.html.twig', [
            'partner' => $partner,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'back_partner_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Partner $partner, EntityManagerInterface $entityManager, LoginLinkHandlerInterface $loginLinkHandler, NotificationService $notificationService): Response
    {
        $this->denyAccessUnlessGranted('PARTNER_EDIT', $partner);
        $form = $this->createForm(PartnerType::class, $partner, [
            'can_edit_territory' => $this->getUser()->isSuperAdmin(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            self::checkFormExtraData($form, $partner, $entityManager, $loginLinkHandler, $notificationService);
            $entityManager->flush();

            return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('back/partner/edit.html.twig', [
            'partner' => $partner,
            'partners' => $entityManager->getRepository(Partner::class)->findAllList($partner->getTerritory()),
            'form' => $form,
        ]);
    }

    #[Route('/switchuser', name: 'back_partner_user_switch', methods: ['POST'])]
    public function switchUser(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('USER_SWITCH', $this->getUser());
        if ($this->isCsrfTokenValid('partner_user_switch', $request->request->get('_token')) && $data = $request->get('user_switch')) {
            $partner = $entityManager->getRepository(Partner::class)->find($data['partner']);
            $user = $entityManager->getRepository(User::class)->find($data['user']);
            $user->setPartner($partner);
//            $user->setStatut(User::STATUS_ARCHIVE);
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', $user->getNomComplet().' transféré avec succès !');

            return $this->redirectToRoute('back_partner_edit', ['id' => $partner->getId()], Response::HTTP_SEE_OTHER);
        }
        $this->addFlash('error', 'Une erreur est survenue lors du transfert...');

        return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{user}/delete', name: 'back_partner_user_delete', methods: ['POST'])]
    public function deleteUser(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('USER_DELETE', $this->getUser());
        if ($this->isCsrfTokenValid('partner_user_delete_'.$user->getId(), $request->request->get('_token'))) {
            $user->setStatut(User::STATUS_ARCHIVE);
            $entityManager->persist($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/checkmail', name: 'back_partner_check_user_email', methods: ['POST'])]
    public function checkMail(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('USER_CHECKMAIL', $this->getUser());
        if ($this->isCsrfTokenValid('partner_checkmail', $request->request->get('_token'))) {
            if ($entityManager->getRepository(User::class)->findOneBy(['email' => $request->get('email')])) {
                return $this->json(['error' => 'email_exist'], 400);
            }
        }

        return $this->json(['success' => 'email_ok']);
    }

    #[Route('/{id}', name: 'back_partner_delete', methods: ['POST'])]
    public function delete(Request $request, Partner $partner, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('PARTNER_DELETE', $partner);
        if ($this->isCsrfTokenValid('partner_delete_'.$partner->getId(), $request->request->get('_token'))) {
            $partner->setIsArchive(true);
            foreach ($partner->getUsers() as $user) {
                $user->setStatut(User::STATUS_ARCHIVE) && $entityManager->persist($user);
            }
            $entityManager->persist($partner);
            $entityManager->flush();
        }

        return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
    }

    private static function checkFormExtraData(FormInterface $form, Partner $partner, EntityManagerInterface $entityManager, LoginLinkHandlerInterface $loginLinkHandler, NotificationService $notificationService)
    {
        if (isset($form->getExtraData()['users'])) {
            foreach ($form->getExtraData()['users'] as $id => $userData) {
                if ('new' !== $id) {
                    $userPartner = $partner->getUsers()->filter(function (User $user) use ($id) {
                        if ($user->getId() === $id) {
                            return $user;
                        }
                    });
                    if (!$userPartner->isEmpty()) {
                        $user = $userPartner->first();
                        self::setUserData($user, $userData['nom'], $userData['prenom'], $userData['roles'], $userData['email'], $userData['isGenerique'], $userData['isMailingActive']);
                        $entityManager->persist($user);
                    }
                } else {
                    foreach ($userData as $newUserData) {
                        $user = new User();
                        $user->setPartner($partner);
                        $user->setTerritory($partner->getTerritory());
                        self::setUserData($user, $newUserData['nom'], $newUserData['prenom'], $newUserData['roles'], $newUserData['email'], $newUserData['isGenerique'], $newUserData['isMailingActive']);
                        $entityManager->persist($user);
                        $loginLinkDetails = $loginLinkHandler->createLoginLink($user);
                        $loginLink = $loginLinkDetails->getUrl();
                        $notificationService->send(NotificationService::TYPE_ACCOUNT_ACTIVATION, $user->getEmail(), ['link' => $loginLink], $user->getTerritory());
                    }
                }
            }
        }
    }

    private static function setUserData(User $user, mixed $nom, mixed $prenom, mixed $roles, mixed $email, bool $isGenerique, bool $isMailingActive)
    {
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setIsGenerique($isGenerique);
        $user->setIsMailingActive($isMailingActive);
        $user->setRoles([$roles]);
        $user->setEmail($email);
    }
}
