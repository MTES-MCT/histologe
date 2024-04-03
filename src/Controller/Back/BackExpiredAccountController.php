<?php

namespace App\Controller\Back;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/comptes-expires')]
class BackExpiredAccountController extends AbstractController
{
    #[Route('/', name: 'back_expired_account_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(UserRepository $userRepository): Response
    {
        $expiredUsagers = $userRepository->findExpiredUsagers();
        $expiredUsers = $userRepository->findExpiredUsers();

        return $this->render('back/expired-account/index.html.twig', [
            'expiredUsagers' => $expiredUsagers,
            'expiredUsers' => $expiredUsers,
        ]);
    }
}
