<?php

namespace App\Controller\Back;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/comptes-inactifs')]
class BackInactiveAccountController extends AbstractController
{
    #[Route('/', name: 'back_inactive_account_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(UserRepository $userRepository): Response
    {
        $expiredUsers = $userRepository->findExpiredUsers();
        $inactiveUsers = $userRepository->findInactiveUsers();

        return $this->render('back/inactive-account/index.html.twig', [
            'expiredUsers' => $expiredUsers,
            'inactiveUsers' => $inactiveUsers,
        ]);
    }
}
