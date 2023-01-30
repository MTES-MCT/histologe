<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Repository\TerritoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo')]
class WidgetSettingsController extends AbstractController
{
    #[Route('/widget-settings', name: 'back_widget_settings')]
    public function index(
        TerritoryRepository $territoryRepository,
    ): JsonResponse {
        $territories = $territoryRepository->findBy(['isActive' => 1]);
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'firstname' => $user->getPrenom(),
            'lastname' => $user->getNom(),
            'role_label' => $user->getRoleLabel(),
            'territories' => $territories,
        ]);
    }
}
