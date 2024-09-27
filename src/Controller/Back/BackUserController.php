<?php

namespace App\Controller\Back;

use App\Dto\SearchUser;
use App\Form\SearchUserType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/utilisateurs')]
#[IsGranted('ROLE_ADMIN_TERRITORY')]
class BackUserController extends AbstractController
{
    public const MAX_LIST_PAGINATION = 25;

    public function __construct(
        #[Autowire(env: 'FEATURE_EXPORT_USERS')]
        bool $featureExportUsers,
    ) {
        if (!$featureExportUsers) {
            throw $this->createNotFoundException();
        }
    }

    #[Route('/', name: 'back_user_index', methods: ['GET'])]
    public function index(
        Request $request,
        UserRepository $userRepository,
    ): Response {
        $searchUser = new SearchUser($this->getUser());
        $form = $this->createForm(SearchUserType::class, $searchUser);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $searchUser = new SearchUser($this->getUser());
            }
        }
        $paginatedUsers = $userRepository->findFilteredPaginated($searchUser, self::MAX_LIST_PAGINATION);

        return $this->render('back/user/index.html.twig', [
            'form' => $form,
            'searchUser' => $searchUser,
            'users' => $paginatedUsers,
            'pages' => (int) ceil(\count($paginatedUsers) / self::MAX_LIST_PAGINATION),
        ]);
    }
}
