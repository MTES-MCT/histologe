<?php

namespace App\Tests\Unit\Service\DashboardWidget;

use App\Service\DashboardWidget\WidgetCard;
use App\Service\DashboardWidget\WidgetCardFactory;
use Faker\Factory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WidgetFactoryTest extends TestCase
{
    public function testCreateWidgetFactoryInstance()
    {
        $faker = Factory::create();
        $url = $faker->url();

        $urlGeneratorMock = $this->createMock(UrlGeneratorInterface::class);
        $urlGeneratorMock
            ->expects($this->once())
            ->method('generate')
            ->with('back_index', ['status_signalement' => 6], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($url);

        $widgetCardFactory = new WidgetCardFactory($urlGeneratorMock);
        $widgetCard = $widgetCardFactory->createInstance(
            'Clôtures globales',
            2,
            'back_index',
            ['status_signalement' => 6]
        );

        $this->assertInstanceOf(WidgetCard::class, $widgetCard);
        $this->assertSame('Clôtures globales', $widgetCard->getLabel());
        $this->assertSame(2, $widgetCard->getCount());
        $this->assertSame($url, $widgetCard->getLink());
    }
}
