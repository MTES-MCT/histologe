<?php

namespace App\Service\DashboardWidget\WidgetLoader;

use App\Service\DashboardWidget\Widget;
use App\Service\DashboardWidget\WidgetDataManagerInterface;
use App\Service\DashboardWidget\WidgetLoaderInterface;
use App\Service\DashboardWidget\WidgetType;

class AffectationPartnerWidgetLoader implements WidgetLoaderInterface
{
    public function __construct(private WidgetDataManagerInterface $widgetDataManager)
    {
    }

    public function load(Widget $widget)
    {
        $widget->setData($this->widgetDataManager->countAffectationPartner($widget->getTerritory()));
    }

    public function supports(string $type): bool
    {
        return WidgetType::WIDGET_TYPE_AFFECTATION_PARTNER === $type;
    }
}
