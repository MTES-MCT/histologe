<?php

namespace App\Service\DashboardWidget\WidgetLoader;

use App\Manager\WidgetDataManager;
use App\Service\DashboardWidget\Widget;
use App\Service\DashboardWidget\WidgetLoaderInterface;
use App\Service\DashboardWidget\WidgetType;
use Doctrine\DBAL\Exception;

class SignalementTerritoryWidgetLoader implements WidgetLoaderInterface
{
    public function __construct(private WidgetDataManager $widgetDataManager)
    {
    }

    /**
     * @throws Exception
     */
    public function load(Widget $widget)
    {
        $widget->setData($this->widgetDataManager->getCountSignalementsByTerritory());
    }

    public function supports(string $type): bool
    {
        return WidgetType::WIDGET_TYPE_SIGNALEMENT_TERRITOIRE === $type;
    }
}
