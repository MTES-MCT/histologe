<?php

namespace App\Tests\Unit\Service\DashboardWidget;

use App\Service\DashboardWidget\Widget;
use App\Service\DashboardWidget\WidgetLoaderCollection;
use App\Service\DashboardWidget\WidgetLoaderInterface;
use App\Service\DashboardWidget\WidgetType;
use PHPUnit\Framework\TestCase;

class WidgetLoaderCollectionTest extends TestCase
{
    public function testLoad()
    {
        $widget = new Widget(WidgetType::WIDGET_TYPE_DATA_KPI);
        $dataKpiWidgetLoader = $this->createMock(WidgetLoaderInterface::class);
        $dataKpiWidgetLoader->expects($this->once())
            ->method('supports')
            ->with($widget->getType())
            ->willReturn(true);

        $dataKpiWidgetLoader->expects($this->once())
            ->method('load')
            ->with($widget);

        $esaboraEventWidgetLoader = $this->createMock(WidgetLoaderInterface::class);
        $esaboraEventWidgetLoader->expects($this->once())
            ->method('supports')
            ->with($widget->getType())
            ->willReturn(false);

        $esaboraEventWidgetLoader
            ->expects($this->never())
            ->method('load');

        $widgetLoaderCollection = new WidgetLoaderCollection([
            $dataKpiWidgetLoader, $esaboraEventWidgetLoader,
        ]);

        $widgetLoaderCollection->load($widget);
    }
}
