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
use App\Service\FormHelper;
use App\Service\ListFilters\SearchUser;
use App\Service\MessageHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/api-user')]
#[IsGranted('ROLE_ADMIN')]
final class UserApiPermissionController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        #[Autowire(param: 'standard_max_list_pagination')]
        private readonly int $maxListPagination,
    ) {
    }

    /**
     * @return array{FormInterface, SearchUser, Paginator<User>}
     */
    private function handleSearch(Request $request, bool $fromSearchParams = false): array
    {
        /** @var User $user */
        $user = $this->getUser();
        $searchUser = new SearchUser($user);
        $form = $this->createForm(SearchUserType::class, $searchUser, ['show_all_fields' => false]);
        FormHelper::handleFormSubmitFromRequestOrSearchParams($form, $request, $fromSearchParams);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchUser = new SearchUser($user);
        }

        /** @var Paginator<User> $paginatedUsers */
        $paginatedUsers = $this->userRepository->findUsersApiPaginator($searchUser, $this->maxListPagination);

        return [$form, $searchUser, $paginatedUsers];
    }

    #[Route('/', name: 'back_api_user_index')]
    public function index(Request $request): Response
    {
        [$form, $searchUser, $paginatedUsers] = $this->handleSearch($request);

        return $this->render('back/user_api_permission/index.html.twig', [
            'form' => $form,
            'users' => $paginatedUsers,
            'searchUser' => $searchUser,
            'pages' => (int) ceil($paginatedUsers->count() / $this->maxListPagination),
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

            $this->addFlash('success', ['title' => 'Permission API ajoutée', 'message' => 'La permission API a bien été créée.']);

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
            $this->addFlash('success', ['title' => 'Permission API modifiée', 'message' => 'La permission API a bien été modifiée.']);

            return $this->redirectToRoute('back_api_user_index');
        }

        return $this->render('back/user_api_permission/edit.html.twig', [
            'user' => $userApiPermission->getUser(),
            'form' => $form,
        ]);
    }

    #[Route(path: '/permission/{id}/delete', name: 'back_api_user_permission_delete', methods: ['POST'])]
    public function delete(UserApiPermission $userApiPermission, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($this->isCsrfTokenValid('user_api_permission_delete', (string) $request->request->get('_token'))) {
            $entityManager->remove($userApiPermission);
            $entityManager->flush();
            $flashMessages[] = ['type' => 'success', 'title' => 'Succès', 'message' => 'La permission API a bien été supprimée.'];
            [, $searchUser, $paginatedUsers] = $this->handleSearch($request, true);
            $htmlTargetContents = [
                [
                    'target' => '#title-list-results',
                    'content' => $this->renderView('back/user_api_permission/_title-list-results.html.twig', ['users' => $paginatedUsers]),
                ],
                [
                    'target' => '#table-list-results',
                    'content' => $this->renderView('back/user_api_permission/_table-list-results.html.twig', [
                        'users' => $paginatedUsers,
                        'searchUser' => $searchUser,
                        'pages' => (int) ceil($paginatedUsers->count() / $this->maxListPagination),
                    ]),
                ],
            ];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
        }
        $this->addFlash('error', MessageHelper::ERROR_MESSAGE_CSRF);
        $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => 'Une erreur est survenue lors de la suppression, veuillez réessayer.'];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
    }

    #[Route(path: '/add', name: 'back_api_user_add', methods: ['GET', 'POST'])]
    public function add(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $hasher,
    ): Response {
        $user = new User();
        $user->setStatut(UserStatus::ACTIVE);
        $user->setRoles([User::ROLE_API_USER]);
        $user->setIsMailingActive(false);
        $user->setIsActivateAccountNotificationEnabled(false);
        $form = $this->createForm(UserApiType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password = UserManager::getComplexRandomPassword();
            $passwordHashed = $hasher->hashPassword($user, $password);
            $user->setPassword($passwordHashed);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', ['title' => 'Utilisateur ajouté', 'message' => 'L\'utilisateur "'.$user->getEmail().'" et son mot de passe "'.$password.'" ont bien été créés.']);

            return $this->redirectToRoute('back_api_user_index');
        }

        return $this->render('back/user_api_permission/add-user.html.twig', [
            'form' => $form,
        ]);
    }
}
