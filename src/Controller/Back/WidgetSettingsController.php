<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Repository\TerritoryRepository;
use App\Security\Voter\UserVoter;
use App\Service\DashboardWidget\WidgetSettings;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
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
        Security $security,
    ): Response {
        $territories = $territoryRepository->findBy(['isActive' => 1]);
        /** @var User $user */
        $user = $this->getUser();
        $canSeeNDE = $security->isGranted(UserVoter::SEE_NDE, $user);

        $widgetSettings = $serializer->serialize(new WidgetSettings($user, $territories, $canSeeNDE), 'json');

        return new Response(
            $widgetSettings,
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
    }
}
