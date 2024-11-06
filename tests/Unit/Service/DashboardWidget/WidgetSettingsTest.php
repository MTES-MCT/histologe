<?php

namespace App\Tests\Unit\Service\DashboardWidget;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Entity\UserPartner;
use App\Service\DashboardWidget\WidgetSettings;
use PHPUnit\Framework\TestCase;

class WidgetSettingsTest extends TestCase
{
    public function testValidWidgetSetting(): void
    {
        $territory = (new Territory())->setName('Ain')->setZip('01');
        $partner = (new Partner())->setNom('Partner')->setTerritory($territory);
        $user = (new User())
            ->setPrenom('John')
            ->setNom('Doe')
            ->setRoles([User::ROLE_USER_PARTNER]);
        $userPartner = (new UserPartner())->setPartner($partner)->setUser($user);
        $user->addUserPartner($userPartner);

        $widgetSettings = new WidgetSettings($user, [
            (new Territory())->setName('Ain')->setZip('01'),
            (new Territory())->setName('Aisne')->setZip('02'),
        ]);

        $this->assertEquals('John', $widgetSettings->getFirstname());
        $this->assertEquals('Doe', $widgetSettings->getLastname());
        $this->assertEquals('Agent', $widgetSettings->getRoleLabel());
        $this->assertCount(2, $widgetSettings->getTerritories());
    }
}
