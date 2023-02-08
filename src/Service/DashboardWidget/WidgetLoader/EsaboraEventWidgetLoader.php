<?php

namespace App\Service\DashboardWidget\WidgetLoader;

use App\Entity\JobEvent;
use App\Service\DashboardWidget\Widget;
use App\Service\DashboardWidget\WidgetDataManagerInterface;
use App\Service\DashboardWidget\WidgetLoaderInterface;
use App\Service\DashboardWidget\WidgetType;
use Doctrine\DBAL\Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EsaboraEventWidgetLoader implements WidgetLoaderInterface
{
    public function __construct(
        private WidgetDataManagerInterface $widgetDataManager,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    /**
     * @throws Exception
     */
    public function load(Widget $widget)
    {
        $params = $this->parameterBag->get($widget->getType());
        $widget->setData(
            $this->widgetDataManager->findLastJobEventByType(
                JobEvent::TYPE_JOB_EVENT_ESABORA,
                $params['day_period']
            )
        );
    }

    public function supports(string $type): bool
    {
        return WidgetType::WIDGET_TYPE_ESABORA_EVENTS === $type;
    }
}
