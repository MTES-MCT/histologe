<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

#[Route('/bo/comptes-archives')]
class BackAccountController extends AbstractController
{
    public const DEFAULT_TERRITORY_AIN = 1;

    #[Route('/', name: 'back_account_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UserRepository $userRepository,
        TerritoryRepository $territoryRepository,
        PartnerRepository $partnerRepository
    ): Response {
        // TODO : limiter aux super admins, créer un nouveau droit ?
        $this->denyAccessUnlessGranted('USER_EDIT', $this->getUser());
        $page = $request->get('page') ?? 1;
        /** @var User $user */
        $user = $this->getUser();

        $territory = empty($request->get('territory')) ? null : (int) $request->get('territory');
        if ($territory) {
            $currentTerritory = $territoryRepository->find($territory);
        } else {
            $currentTerritory = null;
        }

        $partner = empty($request->get('partner')) ? null : (int) $request->get('partner');
        if ($partner) {
            $currentPartner = $partnerRepository->find($partner);
        } else {
            $currentPartner = null;
        }

        $filterTerms = empty($request->get('userTerms')) ? null : $request->get('userTerms');

        $paginatedArchivedUsers = $userRepository->findAllArchived($currentTerritory, $currentPartner, $filterTerms, (int) $page);

        if (Request::METHOD_POST === $request->getMethod()) {
            $currentTerritory = $territoryRepository->find((int) $request->request->get('territory'));
            $currentPartner = $partnerRepository->find((int) $request->request->get('partner'));
            $userTerms = $request->request->get('bo-filters-usersterms');

            return $this->redirect($this->generateUrl('back_account_index', [
                'page' => 1,
                'territory' => ($currentTerritory ? $currentTerritory->getId() : null),
                'partner' => ($currentPartner ? $currentPartner->getId() : null),
                'userTerms' => $userTerms,
            ]));
        }

        $totalArchivedUsers = \count($paginatedArchivedUsers);

        return $this->render('back/account/index.html.twig', [
            'currentTerritory' => $currentTerritory,
            'currentPartner' => $currentPartner,
            'userTerms' => $filterTerms,
            'territories' => $territoryRepository->findAllList(),
            'partners' => $partnerRepository->findAllList($currentTerritory),
            'users' => $paginatedArchivedUsers,
            'total' => $totalArchivedUsers,
            'page' => $page,
            'pages' => (int) ceil($totalArchivedUsers / User::MAX_LIST_PAGINATION),
        ]);
    }

    #[Route('/{id}/reactiver', name: 'back_account_reactiver', methods: ['GET', 'POST'])]
    public function reactiver(
        Request $request,
        User $account,
        UserRepository $userRepository,
        TerritoryRepository $territoryRepository,
        PartnerRepository $partnerRepository,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService,
        LoginLinkHandlerInterface $loginLinkHandler,
    ): Response {
        // TODO : limiter aux super admins, créer un nouveau droit ?
        $this->denyAccessUnlessGranted('USER_EDIT', $this->getUser());
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(UserType::class, $account, [
            'can_edit_email' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $account->setStatut(User::STATUS_ACTIVE);
            $entityManager->flush();
            $this->addFlash('success', 'Réactivation du compte effectuée.');

            if (\in_array('ROLE_USER_PARTNER', $user->getRoles()) || \in_array('ROLE_ADMIN_PARTNER', $user->getRoles()) || \in_array('ROLE_ADMIN_TERRITORY', $user->getRoles()) || \in_array('ROLE_ADMIN', $user->getRoles())) {
                $loginLinkDetails = $loginLinkHandler->createLoginLink($account);
                $loginLink = $loginLinkDetails->getUrl();
                $notificationService->send(
                    NotificationService::TYPE_ACCOUNT_ACTIVATION,
                    $account->getEmail(),
                    ['link' => $loginLink],
                    $account->getTerritory()
                );
            }

            return $this->redirectToRoute('back_account_index', [
                'id' => $account->getId(),
            ]);
        }

        $this->displayErrors($form);

        return $this->renderForm('back/account/edit.html.twig', [
            'user' => $account,
            'territories' => $territoryRepository->findAllList(),
            'partners' => $partnerRepository->findAllList(null),
            'form' => $form,
        ]);
    }

    private function displayErrors(FormInterface $form): void
    {
        /** @var FormError $error */
        foreach ($form->getErrors(true) as $error) {
            $this->addFlash('error', $error->getMessage());
        }
    }
}
