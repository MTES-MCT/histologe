<?php

namespace App\Tests\Unit\Service\DashboardWidget;

use App\Service\DashboardWidget\WidgetCard;
use Faker\Factory;
use PHPUnit\Framework\TestCase;

class WidgetCardTest extends TestCase
{
    public function testValidWidgetCard(): void
    {
        $faker = Factory::create();
        $url = $faker->url();
        $widgetCard = new WidgetCard('Nouveaux signalements', 5, $url);

        $this->assertEquals('Nouveaux signalements', $widgetCard->getLabel());
        $this->assertEquals(5, $widgetCard->getCount());
        $this->assertEquals($url, $widgetCard->getLink());
    }
}
