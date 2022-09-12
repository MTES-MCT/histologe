<?php

namespace App\Tests\Unit\Command;

use App\Command\FillCommuneListCommand;
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
            $resultZip = FillCommuneListCommand::getZipCodeByCodeCommune($codeCommune);
            $this->assertEquals($expectedZip, $resultZip);
        }
    }
}
