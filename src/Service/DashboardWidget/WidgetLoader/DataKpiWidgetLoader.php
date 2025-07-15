<?php

namespace App\Service\DashboardWidget\WidgetLoader;

use App\Service\DashboardWidget\Widget;
use App\Service\DashboardWidget\WidgetDataManagerInterface;
use App\Service\DashboardWidget\WidgetType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @deprecated This class will be removed once the FEATURE_NEW_DASHBOARD feature flag is removed.
 * Please refer to the `App\Service\DashboardTabPanel` namespace for the new dashboard.
 */
class DataKpiWidgetLoader extends AbstractWidgetLoader
{
    protected ?string $widgetType = WidgetType::WIDGET_TYPE_DATA_KPI;

    public function __construct(
        protected ParameterBagInterface $parameterBag,
        protected WidgetDataManagerInterface $widgetDataManager,
    ) {
        parent::__construct($this->parameterBag);
    }

    public function load(Widget $widget): void
    {
        parent::load($widget);
        $widget->setData($this->widgetDataManager->countDataKpi($widget->getTerritories(), $this->widgetParameter['data']));
    }
}
