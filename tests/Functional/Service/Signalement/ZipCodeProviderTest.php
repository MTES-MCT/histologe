<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Service\Signalement\ZipcodeProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ZipCodeProviderTest extends KernelTestCase
{
    private ZipcodeProvider $zipcodeProvider;

    protected function setUp(): void
    {
        $this->zipcodeProvider = static::getContainer()->get(ZipcodeProvider::class);
    }

    public function testGetZipCodeTerritory(): void
    {
        $this->assertEquals(
            '2A',
            $this->zipcodeProvider->getTerritoryByInseeCode('20167')->getZip(),
        );
        $this->assertEquals(
            '2A',
            $this->zipcodeProvider->getTerritoryByInseeCode('20000')->getZip(),
        );
        $this->assertEquals(
            '2B',
            $this->zipcodeProvider->getTerritoryByInseeCode('20200')->getZip(),
        );
        $this->assertEquals(
            '2B',
            $this->zipcodeProvider->getTerritoryByInseeCode('20600')->getZip(),
        );
        $this->assertEquals(
            '974',
            $this->zipcodeProvider->getTerritoryByInseeCode('97400')->getZip()
        );
        $this->assertEquals(
            '972',
            $this->zipcodeProvider->getTerritoryByInseeCode('97200')->getZip()
        );
        $this->assertEquals(
            '13',
            $this->zipcodeProvider->getTerritoryByInseeCode('13002')->getZip()
        );
        $this->assertEquals(
            '69',
            $this->zipcodeProvider->getTerritoryByInseeCode('69123')->getZip()
        );
        $this->assertEquals(
            '69A',
            $this->zipcodeProvider->getTerritoryByInseeCode('69060')->getZip()
        );
    }
}
