<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Service\Signalement\ZipcodeProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ZipCodeProviderTest extends KernelTestCase
{
    public function testGetZipCodeTerritory(): void
    {
        $this->assertEquals(
            ZipcodeProvider::CORSE_DU_SUD_CODE_DEPARTMENT_2A,
            ZipcodeProvider::getZipCode('20167'),
        );
        $this->assertEquals(
            ZipcodeProvider::CORSE_DU_SUD_CODE_DEPARTMENT_2A,
            ZipcodeProvider::getZipCode('20000'),
        );
        $this->assertEquals(
            ZipcodeProvider::HAUTE_CORSE_CODE_DEPARTMENT_2B,
            ZipcodeProvider::getZipCode('20200')
        );
        $this->assertEquals(
            ZipcodeProvider::HAUTE_CORSE_CODE_DEPARTMENT_2B,
            ZipcodeProvider::getZipCode('20600')
        );
        $this->assertEquals(
            ZipcodeProvider::LA_REUNION_CODE_DEPARTMENT_974,
            ZipcodeProvider::getZipCode('97400')
        );
        $this->assertEquals(
            ZipcodeProvider::MARTINIQUE_CODE_DEPARTMENT_972,
            ZipcodeProvider::getZipCode('97200')
        );
        $this->assertEquals(
            '13',
            ZipcodeProvider::getZipCode('13002')
        );
    }
}
