<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Form\SearchUserType;
use App\Repository\UserRepository;
use App\Service\ListFilters\SearchUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo')]
#[IsGranted('ROLE_ADMIN')]
final class UserApiPermissionController extends AbstractController
{
    #[Route('/permissions-utilisateurs', name: 'back_permissions_users_index')]
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
}
