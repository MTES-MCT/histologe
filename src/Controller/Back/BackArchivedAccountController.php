<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/comptes-archives')]
class BackArchivedAccountController extends AbstractController
{
    #[Route('/', name: 'back_account_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        UserRepository $userRepository,
        TerritoryRepository $territoryRepository,
        PartnerRepository $partnerRepository,
    ): Response {
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

        if ($request->isMethod(Request::METHOD_POST)) {
            $isNoneTerritory = 'none' == $request->request->get('territory');
            $currentTerritory = $territoryRepository->find((int) $request->request->get('territory'));
            $isNonePartner = 'none' == $request->request->get('partner');
            $currentPartner = $partnerRepository->find((int) $request->request->get('partner'));
            $userTerms = $request->request->get('userTerms');

            return $this->redirect($this->generateUrl('back_account_index', [
                'page' => 1,
                'territory' => $isNoneTerritory ? 'none' : $currentTerritory?->getId(),
                'partner' => $isNonePartner ? 'none' : $currentPartner?->getId(),
                'userTerms' => $userTerms,
            ]));
        }

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
    #[IsGranted('ROLE_ADMIN')]
    public function reactiver(
        Request $request,
        User $user,
        TerritoryRepository $territoryRepository,
        PartnerRepository $partnerRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        NotificationMailerRegistry $notificationMailerRegistry,
    ): Response {
        $isUserUnlinked = !$user->getPartner();

        if ((User::STATUS_ARCHIVE !== $user->getStatut() && !$isUserUnlinked) || $user->getAnonymizedAt()) {
            $this->addFlash('error', 'Ce compte ne peut pas être réactivé.');

            return $this->redirect($this->generateUrl('back_account_index'));
        }

        $form = $this->createForm(UserType::class, $user, [
            'can_edit_email' => false,
        ]);
        $form->handleRequest($request);

        $untaggedEmail = explode(User::SUFFIXE_ARCHIVED, $user->getEmail())[0];
        $userExist = $userRepository->findOneByEmailExcepted($untaggedEmail, $user);
        if ($userExist) {
            $this->addFlash('error', 'Un utilisateur existe déjà avec cette adresse e-mail. '
            .$userExist->getNomComplet().' ( id '.$userExist->getId().' ) avec le rôle '
            .$userExist->getRoleLabel());
        }

        $partnerExist = $partnerRepository->findOneBy(['email' => $untaggedEmail]);
        if ($partnerExist) {
            $this->addFlash('error', 'Un partenaire existe déjà avec cette adresse e-mail. '
            .$partnerExist->getNom().' ( id '.$partnerExist->getId().' ) dans le territoire '
            .$partnerExist->getTerritory()->getName());
        }

        if (!$userExist && !$partnerExist && $form->isSubmitted() && $form->isValid()) {
            $user->setStatut(User::STATUS_ACTIVE);
            $user->setEmail($untaggedEmail);
            $entityManager->flush();
            $this->addFlash('success', 'Réactivation du compte effectuée.');

            $notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_ACCOUNT_REACTIVATION,
                    to: $user->getEmail(),
                    user: $user,
                )
            );

            return $this->redirectToRoute('back_account_index');
        }

        $this->displayErrors($form);

        return $this->render('back/account/edit.html.twig', [
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
}
