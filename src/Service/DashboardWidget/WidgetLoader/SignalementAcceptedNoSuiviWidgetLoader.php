<?php

namespace App\Service\DashboardWidget\WidgetLoader;

use App\Manager\WidgetDataManager;
use App\Service\DashboardWidget\Widget;
use App\Service\DashboardWidget\WidgetLoaderInterface;
use App\Service\DashboardWidget\WidgetType;

class SignalementAcceptedNoSuiviWidgetLoader implements WidgetLoaderInterface
{
    public function __construct(private WidgetDataManager $widgetDataManager)
    {
    }

    public function load(Widget $widget)
    {
        $widget->setData($this->widgetDataManager->countSignalementAcceptedNoSuivi($widget->getTerritory()));
    }

    public function supports(string $type): bool
    {
        return WidgetType::WIDGET_TYPE_SIGNALEMENT_ACCEPTED_NO_SUIVI === $type;
    }
}
