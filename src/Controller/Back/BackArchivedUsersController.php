<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Entity\UserPartner;
use App\Form\SearchArchivedAccountType;
use App\Form\UserType;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\ListFilters\SearchArchivedAccount;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/comptes-archives')]
class BackArchivedUsersController extends AbstractController
{
    #[Route('/', name: 'back_archived_users_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        UserRepository $userRepository,
        ParameterBagInterface $parameterBag,
    ): Response {
        $searchArchivedAccount = new SearchArchivedAccount();
        $form = $this->createForm(SearchArchivedAccountType::class, $searchArchivedAccount);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchArchivedAccount = new SearchArchivedAccount();
        }
        $maxListPagination = $parameterBag->get('standard_max_list_pagination');
        $paginatedArchivedAccount = $userRepository->findArchivedFilteredPaginated($searchArchivedAccount, $maxListPagination);

        return $this->render('back/account/index.html.twig', [
            'form' => $form,
            'searchArchivedAccount' => $searchArchivedAccount,
            'users' => $paginatedArchivedAccount,
            'pages' => (int) ceil($paginatedArchivedAccount->count() / $maxListPagination),
        ]);
    }

    #[Route('/{id}/reactiver', name: 'back_archived_users_reactiver', methods: ['GET', 'POST'])]
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
        $isUserUnlinked = $user->getPartners()->isEmpty();

        if ((User::STATUS_ARCHIVE !== $user->getStatut() && !$isUserUnlinked) || $user->getAnonymizedAt()) {
            $this->addFlash('error', 'Ce compte ne peut pas être réactivé.');

            return $this->redirect($this->generateUrl('back_archived_users_index'));
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
            // tempPartner is not mapped, so we need to set the partner manually
            foreach ($user->getUserPartners() as $userPartner) {
                $entityManager->remove($userPartner);
            }
            $partner = $form->get('tempPartner')->getData();
            $userPartner = (new UserPartner())->setUser($user)->setPartner($partner);
            $user->addUserPartner($userPartner);
            $entityManager->persist($userPartner);

            $entityManager->flush();
            $this->addFlash('success', 'Réactivation du compte effectuée.');

            $notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_ACCOUNT_REACTIVATION,
                    to: $user->getEmail(),
                    user: $user,
                )
            );

            return $this->redirectToRoute('back_archived_users_index');
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
