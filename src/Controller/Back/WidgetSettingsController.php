<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Repository\TerritoryRepository;
use App\Service\DashboardWidget\WidgetSettings;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/bo')]
class WidgetSettingsController extends AbstractController
{
    #[Route('/widget-settings', name: 'back_widget_settings')]
    public function index(
        TerritoryRepository $territoryRepository,
        SerializerInterface $serializer,
    ): Response {
        $territories = $territoryRepository->findBy(['isActive' => 1]);
        /** @var User $user */
        $user = $this->getUser();

        $widgetSettings = $serializer->serialize(new WidgetSettings($user, $territories), 'json');

        return new Response(
            $widgetSettings,
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
    }
}
