<?php

namespace App\Service\DashboardWidget\WidgetLoader;

use App\Manager\WidgetDataManager;
use App\Service\DashboardWidget\Widget;
use App\Service\DashboardWidget\WidgetLoaderInterface;
use App\Service\DashboardWidget\WidgetType;

class DataKpiWidgetLoader implements WidgetLoaderInterface
{
    public function __construct(private WidgetDataManager $widgetDataManager)
    {
    }

    public function load(Widget $widget)
    {
        $widget->setData($this->widgetDataManager->countDataKpi($widget->getTerritory()));
    }

    public function supports(string $type): bool
    {
        return WidgetType::WIDGET_TYPE_DATA_KPI === $type;
    }
}
