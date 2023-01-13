<?php

namespace App\Controller\Back;

use App\Entity\Partner;
use App\Entity\User;
use App\Form\PartnerType;
use App\Manager\PartnerManager;
use App\Manager\UserManager;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/partner')]
class BackPartnerController extends AbstractController
{
    public const DEFAULT_TERRITORY_AIN = 1;

    #[Route('/', name: 'back_partner_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        PartnerRepository $partnerRepository,
        TerritoryRepository $territoryRepository
    ): Response {
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
    public function new(Request $request, EntityManagerInterface $entityManager): Response
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

            $entityManager->persist($partner);
            $entityManager->flush();
            $this->addFlash('success', 'Mise à jour partenaire effectuée.');

            return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
        }

        $this->displayErrors($form);

        return $this->renderForm('back/partner/edit.html.twig', [
            'partner' => $partner,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'back_partner_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Partner $partner,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('PARTNER_EDIT', $partner);
        $form = $this->createForm(PartnerType::class, $partner, [
            'can_edit_territory' => $this->getUser()->isSuperAdmin(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Mise à jour partenaire effectuée.');

            return $this->redirectToRoute('back_partner_edit', [
                'id' => $partner->getId(),
            ]);
        }

        $this->displayErrors($form);

        return $this->renderForm('back/partner/edit.html.twig', [
            'partner' => $partner,
            'partners' => $entityManager->getRepository(Partner::class)->findAllList($partner->getTerritory()),
            'form' => $form,
        ]);
    }

    #[Route('/transferuser', name: 'back_partner_user_transfer', methods: ['POST'])]
    public function transferUser(Request $request, UserManager $userManager, PartnerManager $partnerManager): Response
    {
        $this->denyAccessUnlessGranted('USER_TRANSFER', $this->getUser());
        if (
            $this->isCsrfTokenValid('partner_user_transfer', $request->get('_token'))
            && $data = $request->get('user_transfer')
        ) {
            $partner = $partnerManager->find($data['partner']);
            $user = $userManager->find($data['user']);
            $userManager->transferUserToPartner($user, $partner);
            $this->addFlash('success', $user->getNomComplet().' transféré avec succès !');

            return $this->redirectToRoute('back_partner_edit', ['id' => $partner->getId()], Response::HTTP_SEE_OTHER);
        }
        $this->addFlash('error', 'Une erreur est survenue lors du transfert...');

        return $this->redirectToRoute('back_partner_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/deleteuser', name: 'back_partner_user_delete', methods: ['POST'])]
    public function deleteUser(
        Request $request,
        UserManager $userManager,
        PartnerManager $partnerManager,
        NotificationService $notificationService
    ): Response {
        $this->denyAccessUnlessGranted('USER_DELETE', $this->getUser());
        if (
            $this->isCsrfTokenValid('partner_user_delete', $request->get('_token'))
            && $data = $request->get('user_delete')
        ) {
            $user = $userManager->find($data['user']);
            $partner = $partnerManager->find($data['partner']);
            $user->setStatut(User::STATUS_ARCHIVE);
            $userManager->save($user);
            $notificationService->send(
                NotificationService::TYPE_ACCOUNT_DELETE,
                $user->getEmail(),
                [],
                $user->getTerritory()
            );
            $this->addFlash('success', $user->getNomComplet().' supprimé avec succès !');

            return $this->redirectToRoute('back_partner_edit', ['id' => $partner->getId()], Response::HTTP_SEE_OTHER);
        }
        $this->addFlash('error', 'Une erreur est survenue lors de la suppression...');

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

    private function displayErrors(FormInterface $form): void
    {
        /** @var FormError $error */
        foreach ($form->getErrors(true) as $error) {
            $this->addFlash('error', $error->getMessage());
        }
    }
}
