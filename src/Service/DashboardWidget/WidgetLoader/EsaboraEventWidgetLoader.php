<?php

namespace App\Service\DashboardWidget\WidgetLoader;

use App\Entity\JobEvent;
use App\Service\DashboardWidget\Widget;
use App\Service\DashboardWidget\WidgetDataManagerInterface;
use App\Service\DashboardWidget\WidgetType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EsaboraEventWidgetLoader extends AbstractWidgetLoader
{
    protected ?string $widgetType = WidgetType::WIDGET_TYPE_ESABORA_EVENTS;

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
            $this->widgetDataManager->findLastJobEventByType(
                JobEvent::TYPE_JOB_EVENT_ESABORA,
                $this->widgetParameter['data'],
                $widget->getTerritory()
            )
        );
    }
}
