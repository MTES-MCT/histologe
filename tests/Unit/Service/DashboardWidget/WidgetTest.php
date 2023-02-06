<?php

namespace App\Tests\Unit\Service\DashboardWidget;

use App\Entity\Territory;
use App\Service\DashboardWidget\Widget;
use App\Service\DashboardWidget\WidgetType;
use Monolog\Test\TestCase;

class WidgetTest extends TestCase
{
    public function testValidWidget(): void
    {
        $widget = (new Widget())
            ->setData(['content' => 'Hello world'])
            ->setTerritory((new Territory())->setName('Ain')->setZip('01'))
            ->setParameters(['page' => 10])
            ->setType(WidgetType::WIDGET_TYPE_SIGNALEMENT_ACCEPTED_NO_SUIVI);

        $this->assertEquals('01', $widget->getTerritory()->getZip());
        $this->assertEquals('signalements-acceptes-sans-suivi', $widget->getType());
        $this->assertArrayHasKey('page', $widget->getParameters());
        $this->assertArrayHasKey('content', $widget->getData());
    }
}
