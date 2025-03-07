<?php

namespace App\Tests\Unit\Utils;

use App\Utils\CommuneHelper;
use PHPUnit\Framework\TestCase;

class CommuneHelperTest extends TestCase
{
    public function testGetCommuneFromArrondissement()
    {
        $this->assertEquals('Marseille', CommuneHelper::getCommuneFromArrondissement('Marseille'));
        $this->assertEquals('Marseille', CommuneHelper::getCommuneFromArrondissement('Marseille 1er Arrondissement'));
        $this->assertEquals('Marseille', CommuneHelper::getCommuneFromArrondissement('Marseille 16e Arrondissement'));
        $this->assertEquals('Lyon', CommuneHelper::getCommuneFromArrondissement('Lyon 1er Arrondissement'));
        $this->assertEquals('Lyon', CommuneHelper::getCommuneFromArrondissement('Lyon 9e Arrondissement'));
        $this->assertEquals('Montpellier', CommuneHelper::getCommuneFromArrondissement('Montpellier'));
        $this->assertNull(CommuneHelper::getCommuneFromArrondissement(null));
    }
}
