<?php

namespace App\Service\DashboardWidget\WidgetLoader;

use App\Service\DashboardWidget\Widget;
use App\Service\DashboardWidget\WidgetDataManagerInterface;
use App\Service\DashboardWidget\WidgetType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SignalementAcceptedNoSuiviWidgetLoader extends AbstractWidgetLoader
{
    protected ?string $widgetType = WidgetType::WIDGET_TYPE_SIGNALEMENT_ACCEPTED_NO_SUIVI;

    public function __construct(
        protected ParameterBagInterface $parameterBag,
        protected WidgetDataManagerInterface $widgetDataManager
    ) {
        parent::__construct($this->parameterBag);
    }

    public function load(Widget $widget): void
    {
        parent::load($widget);
        $widget->setData(
            $this->widgetDataManager->countSignalementAcceptedNoSuivi(
                $widget->getTerritory(),
                $this->widgetParameter['data']
            )
        );
    }
}
