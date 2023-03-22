<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Manager\TerritoryManager;
use App\Service\DashboardWidget\Widget;
use App\Service\DashboardWidget\WidgetLoaderCollection;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/bo')]
class WidgetController extends AbstractController
{
    #[Route('/widget/{widgetType}', name: 'back_dashboard_widget')]
    public function index(
        Request $request,
        WidgetLoaderCollection $widgetLoaderCollection,
        SerializerInterface $serializer,
        TerritoryManager $territoryManager,
        LoggerInterface $logger,
        string $widgetType
    ): Response {
        if ($this->isGranted('ROLE_ADMIN')) {
            $territory = $territoryManager->find((int) $request->get('territory'));
        } else {
            /** @var User $user */
            $user = $this->getUser();
            $territory = $user->getTerritory() ?? $user->getPartner()->getTerritory();
            if (null === $territory) {
                $logger->critical(sprintf('%s has no territory', $user->getEmail()));

                return $this->json([], Response::HTTP_BAD_REQUEST);
            }
        }
        $widget = new Widget($widgetType, $territory);
        $this->denyAccessUnlessGranted('VIEW_WIDGET', $widget);
        $widgetLoaderCollection->load($widget);

        return new Response(
            $serializer->serialize($widget, 'json'),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
    }
}
