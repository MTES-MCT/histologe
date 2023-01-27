<?php

namespace App\Controller;

use App\Manager\TerritoryManager;
use App\Service\DashboardWidget\Widget;
use App\Service\DashboardWidget\WidgetLoaderCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class DashboardWidgetController extends AbstractController
{
    #[Route('/widget/{widgetType}', name: 'back_dashboard_widget')]
    public function index(
        Request $request,
        WidgetLoaderCollection $widgetLoaderCollection,
        SerializerInterface $serializer,
        TerritoryManager $territoryManager,
        string $widgetType
    ): Response {
        $territory = $territoryManager->find((int) $request->get('territory'));
        $widget = new Widget($widgetType, $territory);
        $widgetLoaderCollection->load($widget);

        return new Response(
            $serializer->serialize($widget, 'json'),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
    }
}
