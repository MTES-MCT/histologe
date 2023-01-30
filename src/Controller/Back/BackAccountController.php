<?php

namespace App\Controller\Back;

use App\Entity\Partner;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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

        // TODO : limiter aux super admins, créer un nouveau droit ?
        if ($this->isGranted('ROLE_ADMIN')) {
            // $territory = empty($request->get('territory')) ? self::DEFAULT_TERRITORY_AIN : (int) $request->get('territory');
            // $currentTerritory = $territoryRepository->find($territory);
        }
        // $currentTerritory = $user->getTerritory();

        $paginatedArchivedUsers = $userRepository->findAllArchived(null, null, (int) $page);

        // if (Request::METHOD_POST === $request->getMethod()) {
        //     $currentTerritory = $territoryRepository->find((int) $request->request->get('territory'));

        //     return $this->redirect($this->generateUrl('back_partner_index', [
        //         'page' => 1,
        //         'territory' => $currentTerritory->getId(),
        //     ]));
        // }

        $totalArchivedUsers = \count($paginatedArchivedUsers);

        return $this->render('back/account/index.html.twig', [
            'currentTerritory' => null,
            'currentPartner' => null,
            'territories' => $territoryRepository->findAllList(),
            'partners' => $partnerRepository->findAllList(null),
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
        EntityManagerInterface $entityManager
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

            return $this->redirectToRoute('back_account_reactiver', [
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
