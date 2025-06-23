<?php

namespace App\Tests\Unit\Service\DashboardWidget;

use App\Dto\CountPartner;
use App\Dto\CountSignalement;
use App\Dto\CountSuivi;
use App\Dto\CountUser;
use App\Service\DashboardWidget\WidgetDataKpi;
use PHPUnit\Framework\TestCase;

class WidgetDataKpiTest extends TestCase
{
    public function testValidWidgetDataKpi(): void
    {
        $widgetDataKpi = new WidgetDataKpi(
            ['cardMesAffectations' => ['Mes affectations', 2, 'back_signalements_index']],
            new CountSignalement(20, 5, 5, 5, 5),
            new CountSuivi(20, 50, 10),
            new CountUser(10, 10),
            new CountPartner(3)
        );

        $this->assertInstanceOf(CountSignalement::class, $widgetDataKpi->getCountSignalement());
        $this->assertInstanceOf(CountSuivi::class, $widgetDataKpi->getCountSuivi());
        $this->assertInstanceOf(CountUser::class, $widgetDataKpi->getCountUser());
        $this->assertInstanceOf(CountPartner::class, $widgetDataKpi->getCountPartner());
        $this->assertArrayHasKey('cardMesAffectations', $widgetDataKpi->getWidgetCards());
    }
}
