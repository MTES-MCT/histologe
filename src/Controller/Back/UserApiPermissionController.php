<?php

namespace App\Controller\Back;

use App\Entity\Enum\UserStatus;
use App\Entity\User;
use App\Entity\UserApiPermission;
use App\Form\SearchUserType;
use App\Form\UserApiPermissionType;
use App\Form\UserApiType;
use App\Manager\UserManager;
use App\Repository\UserRepository;
use App\Service\ListFilters\SearchUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/api-user')]
#[IsGranted('ROLE_ADMIN')]
final class UserApiPermissionController extends AbstractController
{
    #[Route('/', name: 'back_api_user_index')]
    public function index(
        UserRepository $userRepository,
        Request $request,
        #[Autowire(param: 'standard_max_list_pagination')] int $maxListPagination,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $searchUser = new SearchUser($user);
        $form = $this->createForm(SearchUserType::class, $searchUser, ['show_all_fields' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $searchUser = new SearchUser($user);
        }

        $paginatedUsers = $userRepository->findUsersApiPaginator($searchUser, $maxListPagination);

        return $this->render('back/user_api_permission/index.html.twig', [
            'form' => $form,
            'users' => $paginatedUsers,
            'searchUser' => $searchUser,
            'pages' => (int) ceil($paginatedUsers->count() / $maxListPagination),
        ]);
    }

    #[Route(path: '/permission/{id}/create', name: 'back_api_user_permission_create', methods: ['GET', 'POST'])]
    public function permissionCreate(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$user->isApiUser()) {
            throw $this->createAccessDeniedException();
        }
        $userApiPermission = new UserApiPermission();
        $userApiPermission->setUser($user);
        $form = $this->createForm(UserApiPermissionType::class, $userApiPermission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($userApiPermission);
            $entityManager->flush();

            $this->addFlash('success', 'Permission API créée avec succès.');

            return $this->redirectToRoute('back_api_user_index');
        }

        return $this->render('back/user_api_permission/create.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route(path: '/permission/{id}/edit', name: 'back_api_user_permission_edit', methods: ['GET', 'POST'])]
    public function edit(UserApiPermission $userApiPermission, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserApiPermissionType::class, $userApiPermission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Permission API modifiée avec succès.');

            return $this->redirectToRoute('back_api_user_index');
        }

        return $this->render('back/user_api_permission/edit.html.twig', [
            'user' => $userApiPermission->getUser(),
            'form' => $form,
        ]);
    }

    #[Route(path: '/permission/{id}/delete', name: 'back_api_user_permission_delete', methods: ['POST'])]
    public function delete(UserApiPermission $userApiPermission, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('user_api_permission_delete', $request->request->get('_token'))) {
            $entityManager->remove($userApiPermission);
            $entityManager->flush();
            $this->addFlash('success', 'Permission API supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide, veuillez réessayer.');
        }

        return $this->redirectToRoute('back_api_user_index');
    }

    #[Route(path: '/add-user', name: 'back_user_api_permission_add_user', methods: ['GET', 'POST'])]
    public function showAddUserForm(
        Request $request,
        EntityManagerInterface $entityManager,
        UserManager $userManager,
        UserPasswordHasherInterface $hasher,
    ): Response {
        $user = new User();
        $user->setStatut(UserStatus::ACTIVE);
        $user->setRoles([User::ROLE_API_USER]);
        $user->setPrenom('API');
        $user->setNom('API');
        $user->setIsMailingActive(false);
        $user->setIsActivateAccountNotificationEnabled(false);
        $form = $this->createForm(UserApiType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = $userManager->getComplexRandomPassword();
            $passwordHashed = $hasher->hashPassword($user, $password);
            $user->setPassword($passwordHashed);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur "'.$user->getEmail().'" créé avec succès avec le mot de passe "'.$password.'".');

            return $this->redirectToRoute('back_user_api_permission_index');
        }

        return $this->render('back/user_api_permission/add-user.html.twig', [
            'form' => $form,
        ]);
    }
}
