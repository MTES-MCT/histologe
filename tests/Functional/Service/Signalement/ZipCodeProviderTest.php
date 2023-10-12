<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Service\Signalement\ZipcodeProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ZipCodeProviderTest extends KernelTestCase
{
    private ZipcodeProvider $zipcodeProvider;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->zipcodeProvider = $container->get(ZipcodeProvider::class);
    }

    public function testGetZipCodeTerritory(): void
    {
        $this->assertEquals(
            ZipcodeProvider::CORSE_DU_SUD_CODE_DEPARTMENT_2A,
            $this->zipcodeProvider->getZipCode('20167'),
        );
        $this->assertEquals(
            ZipcodeProvider::CORSE_DU_SUD_CODE_DEPARTMENT_2A,
            $this->zipcodeProvider->getZipCode('20000'),
        );
        $this->assertEquals(
            ZipcodeProvider::HAUTE_CORSE_CODE_DEPARTMENT_2B,
            $this->zipcodeProvider->getZipCode('20200')
        );
        $this->assertEquals(
            ZipcodeProvider::HAUTE_CORSE_CODE_DEPARTMENT_2B,
            $this->zipcodeProvider->getZipCode('20600')
        );
        $this->assertEquals(
            ZipcodeProvider::LA_REUNION_CODE_DEPARTMENT_974,
            $this->zipcodeProvider->getZipCode('97400')
        );
        $this->assertEquals(
            ZipcodeProvider::MARTINIQUE_CODE_DEPARTMENT_972,
            $this->zipcodeProvider->getZipCode('97200')
        );
        $this->assertEquals(
            '13',
            $this->zipcodeProvider->getZipCode('13002')
        );
    }
}
