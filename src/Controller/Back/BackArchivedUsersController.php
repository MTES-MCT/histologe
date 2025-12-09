<?php

namespace App\Controller\Back;

use App\Entity\Enum\UserStatus;
use App\Entity\User;
use App\Entity\UserPartner;
use App\Form\SearchArchivedUserType;
use App\Form\UserType;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\ListFilters\SearchArchivedUser;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
        #[Autowire(param: 'standard_max_list_pagination')] int $maxListPagination,
    ): Response {
        $searchArchivedUser = new SearchArchivedUser();
        $form = $this->createForm(SearchArchivedUserType::class, $searchArchivedUser);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchArchivedUser = new SearchArchivedUser();
        }
        $paginatedArchivedUser = $userRepository->findArchivedFilteredPaginated($searchArchivedUser, $maxListPagination);

        return $this->render('back/user_archived/index.html.twig', [
            'form' => $form,
            'searchArchivedUser' => $searchArchivedUser,
            'users' => $paginatedArchivedUser,
            'pages' => (int) ceil($paginatedArchivedUser->count() / $maxListPagination),
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

        if ((UserStatus::ARCHIVE !== $user->getStatut() && !$isUserUnlinked) || $user->getAnonymizedAt()) {
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
            $message = 'Un utilisateur existe déjà avec cette adresse e-mail. '.$userExist->getNomComplet().' ( id '.$userExist->getId().' ) avec le rôle '.$userExist->getRoleLabel();
            $this->addFlash('error', ['title' => 'Adresse e-mail existante', 'message' => $message]);
        }

        $partnerExist = $partnerRepository->findOneBy(['email' => $untaggedEmail]);
        if ($partnerExist) {
            $message = 'Un partenaire existe déjà avec cette adresse e-mail. '.$partnerExist->getNom().' ( id '.$partnerExist->getId().' ) dans le territoire '.$partnerExist->getTerritory()->getName();
            $this->addFlash('error', ['title' => 'Adresse e-mail existante', 'message' => $message]);
        }

        if (!$userExist && !$partnerExist && $form->isSubmitted() && $form->isValid()) {
            $user->setStatut(UserStatus::ACTIVE);
            $user->setEmail($untaggedEmail);
            // tempPartner is not mapped, so we need to set the partner manually
            foreach ($user->getUserPartners() as $userPartner) {
                $entityManager->remove($userPartner);
            }
            // we need to flush the removed userPartners before flushing the new one to prevent a duplicate user_partner because doctrine perform the insert before the delete
            $entityManager->flush();

            $partner = $form->get('tempPartner')->getData();
            $userPartner = (new UserPartner())->setUser($user)->setPartner($partner);
            $user->addUserPartner($userPartner);
            $entityManager->persist($userPartner);

            $entityManager->flush();
            $this->addFlash('success', ['title' => 'Compte réactivé', 'message' => 'Le compte a bien été réactivé']);

            $notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_ACCOUNT_REACTIVATION,
                    to: $user->getEmail(),
                    user: $user,
                )
            );

            return $this->redirectToRoute('back_archived_users_index');
        }

        return $this->render('back/user_archived/edit.html.twig', [
            'user' => $user,
            'territories' => $territoryRepository->findAllList(),
            'partners' => $partnerRepository->findAllList(null),
            'form' => $form,
        ]);
    }
}
