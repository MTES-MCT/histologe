<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Manager\TerritoryManager;
use App\Service\DashboardWidget\Widget;
use App\Service\DashboardWidget\WidgetLoaderCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @deprecated This class will be removed once the FEATURE_NEW_DASHBOARD feature flag is removed
 * @see DashboardTabPanelController
 */
#[Route('/bo')]
class WidgetController extends AbstractController
{
    #[Route('/widget/{widgetType}', name: 'back_dashboard_widget')]
    public function index(
        Request $request,
        WidgetLoaderCollection $widgetLoaderCollection,
        TerritoryManager $territoryManager,
        string $widgetType,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $territories = [];
        $authorizedTerritories = $user->getPartnersTerritories();
        $territoryId = $request->get('territory');
        if ($territoryId && ($this->isGranted('ROLE_ADMIN') || isset($authorizedTerritories[$territoryId]))) {
            $territory = $territoryManager->find((int) $territoryId);
            if ($territory) {
                $territories[$territory->getId()] = $territory;
            }
        } elseif (!$this->isGranted('ROLE_ADMIN')) {
            $territories = $user->getPartnersTerritories();
        }
        $widget = new Widget($widgetType, $territories);
        $this->denyAccessUnlessGranted('VIEW_WIDGET', $widget);
        $widgetLoaderCollection->load($widget);

        return $this->json(
            $widget,
            Response::HTTP_OK,
            ['content-type' => 'application/json'],
            ['groups' => ['widget:read']]
        );
    }
}
