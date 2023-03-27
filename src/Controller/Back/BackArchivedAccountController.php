<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Security\BackOfficeAuthenticator;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/bo/comptes-archives')]
class BackArchivedAccountController extends AbstractController
{
    public const DEFAULT_TERRITORY_AIN = 1;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    #[Route('/', name: 'back_account_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UserRepository $userRepository,
        TerritoryRepository $territoryRepository,
        PartnerRepository $partnerRepository
    ): Response {
        $this->denyAccessUnlessGranted('USER_REACTIVE', $this->getUser());
        $page = $request->get('page') ?? 1;

        $isNoneTerritory = 'none' == $request->get('territory');
        $currentTerritory = $isNoneTerritory ? null : $territoryRepository->find((int) $request->get('territory'));
        $isNonePartner = 'none' == $request->get('partner');
        $currentPartner = $isNonePartner ? null : $partnerRepository->find((int) $request->get('partner'));
        $userTerms = $request->get('userTerms');

        $paginatedArchivedUsers = $userRepository->findAllArchived(
            territory: $currentTerritory,
            isNoneTerritory: $isNoneTerritory,
            partner: $currentPartner,
            isNonePartner: $isNonePartner,
            filterTerms: $userTerms,
            includeUsagers: false,
            page: (int) $page
        );

        $totalArchivedUsers = \count($paginatedArchivedUsers);

        return $this->render('back/account/index.html.twig', [
            'isNoneTerritory' => $isNoneTerritory,
            'currentTerritory' => $currentTerritory,
            'isNonePartner' => $isNonePartner,
            'currentPartner' => $currentPartner,
            'userTerms' => $userTerms,
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
        User $user,
        TerritoryRepository $territoryRepository,
        PartnerRepository $partnerRepository,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService,
    ): Response {
        $this->denyAccessUnlessGranted('USER_REACTIVE', $this->getUser());

        $isUserUnlinked = (!$user->getTerritory() || !$user->getPartner());

        if (User::STATUS_ARCHIVE !== $user->getStatut() && !$isUserUnlinked) {
            return $this->redirect($this->generateUrl('back_account_index'));
        }

        $form = $this->createForm(UserType::class, $user, [
            'can_edit_email' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setStatut(User::STATUS_ACTIVE);
            $entityManager->flush();
            $this->addFlash('success', 'Réactivation du compte effectuée.');

            $link = $this->generateLink($user);

            $notificationService->send(
                NotificationService::TYPE_ACCOUNT_REACTIVATION,
                $user->getEmail(),
                [
                    'link' => $link,
                    'territoire_name' => $user->getTerritory()?->getName(),
                    'partner_name' => $user->getPartner()->getNom(),
                ],
                $user->getTerritory()
            );

            return $this->redirectToRoute('back_account_index');
        }

        $this->displayErrors($form);

        return $this->renderForm('back/account/edit.html.twig', [
            'user' => $user,
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

    private function generateLink(User $user): string
    {
        return
            $this->parameterBag->get('host_url').
            $this->urlGenerator->generate(BackOfficeAuthenticator::LOGIN_ROUTE, ['token' => $user->getToken()]);
    }
}
