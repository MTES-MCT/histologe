<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Factory\WidgetSettingsFactory;
use App\Repository\TerritoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo')]
class WidgetSettingsController extends AbstractController
{
    #[Route('/widget-settings', name: 'back_widget_settings')]
    public function index(
        WidgetSettingsFactory $widgetSettingsFactory,
        TerritoryRepository $territoryRepository,
        Security $security,
        #[MapQueryParameter] ?int $territoryId = null,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $territory = ($security->isGranted('ROLE_ADMIN') && null !== $territoryId)
            ? $territoryRepository->find($territoryId)
            : $user->getTerritory();

        return $this->json(
            $widgetSettingsFactory->createInstanceFrom($user, $territory),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
    }
}
