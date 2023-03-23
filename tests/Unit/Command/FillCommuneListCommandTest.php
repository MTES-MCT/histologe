<?php

namespace App\Tests\Unit\Command;

use App\DataFixtures\Loader\LoadCommuneData;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FillCommuneListCommandTest extends KernelTestCase
{
    public function testCodeCommuneToZipCode(
    ) {
        $listTest = [
            '1045' => '01',
            '10002' => '10',
            '97121' => '971',
            '2A363' => '2A',
            '2B002' => '2B',
        ];
        foreach ($listTest as $codeCommune => $expectedZip) {
            $resultZip = LoadCommuneData::getZipCodeByCodeCommune($codeCommune);
            $this->assertEquals($expectedZip, $resultZip);
        }
    }
}
