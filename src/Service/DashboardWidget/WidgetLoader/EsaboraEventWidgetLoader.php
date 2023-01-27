<?php

namespace App\Service\DashboardWidget\WidgetLoader;

use App\Repository\JobEventRepository;
use App\Service\DashboardWidget\Widget;
use App\Service\DashboardWidget\WidgetLoaderInterface;
use App\Service\DashboardWidget\WidgetType;
use Doctrine\DBAL\Exception;

class EsaboraEventWidgetLoader implements WidgetLoaderInterface
{
    public function __construct(private JobEventRepository $jobEventRepository)
    {
    }

    /**
     * @throws Exception
     */
    public function load(Widget $widget)
    {
        $widget->setData($this->jobEventRepository->findLastJobEventByType());
    }

    public function supports(string $type): bool
    {
        return WidgetType::WIDGET_TYPE_ESABORA_EVENTS === $type;
    }
}
