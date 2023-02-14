<?php

namespace App\Service\DashboardWidget\WidgetLoader;

use App\Service\DashboardWidget\Widget;
use App\Service\DashboardWidget\WidgetDataManagerInterface;
use App\Service\DashboardWidget\WidgetType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SignalementTerritoryWidgetLoader extends AbstractWidgetLoader
{
    protected ?string $widgetType = WidgetType::WIDGET_TYPE_SIGNALEMENT_TERRITOIRE;

    public function __construct(
        protected ParameterBagInterface $parameterBag,
        protected WidgetDataManagerInterface $widgetDataManager,
    ) {
        parent::__construct($this->parameterBag);
    }

    public function load(Widget $widget): void
    {
        parent::load($widget);
        $widget->setData($this->widgetDataManager->countSignalementsByTerritory($this->widgetParameter['data']));
    }
}
