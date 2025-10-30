<?php

namespace App\Tests\Unit\Dto;

use App\Dto\Settings;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Entity\UserPartner;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    public function testValidSetting(): void
    {
        $territory = (new Territory())->setName('Ain')->setZip('01');
        $partner = (new Partner())->setNom('Partner')->setTerritory($territory);
        $user = (new User())
            ->setPrenom('John')
            ->setNom('Doe')
            ->setRoles([User::ROLE_USER_PARTNER]);
        $userPartner = (new UserPartner())->setPartner($partner)->setUser($user);
        $user->addUserPartner($userPartner);

        $settings = new Settings($user, [
            (new Territory())->setName('Ain')->setZip('01'),
            (new Territory())->setName('Aisne')->setZip('02'),
        ], true);

        $this->assertEquals('John', $settings->getFirstname());
        $this->assertEquals('Doe', $settings->getLastname());
        $this->assertEquals('Agent', $settings->getRoleLabel());
        $this->assertCount(2, $settings->getTerritories());
        $this->assertEquals('1', $settings->getCanSeeNDE());
    }
}
