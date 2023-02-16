<?php

namespace App\Tests\Unit\Dto;

use App\Dto\CountSuivi;
use PHPUnit\Framework\TestCase;

class CountSuiviTest extends TestCase
{
    public function testGetValidCountSuivi(): void
    {
        $countSuivi = new CountSuivi(20.6, 123, 70, 10, 12);
        $this->assertEquals(20.6, $countSuivi->getAverage());
        $this->assertEquals(123, $countSuivi->getPartner());
        $this->assertEquals(70, $countSuivi->getUsager());
        $this->assertEquals(10, $countSuivi->getSignalementNewSuivi());
        $this->assertEquals(12, $countSuivi->getSignalementNoSuivi());
    }

    public function testEmptyCountSuivi(): void
    {
        $countSuivi = new CountSuivi(0, 0, 0, 0, 0);
        $this->assertEquals(0, $countSuivi->getAverage());
        $this->assertEquals(0, $countSuivi->getPartner());
        $this->assertEquals(0, $countSuivi->getUsager());
        $this->assertEquals(0, $countSuivi->getSignalementNewSuivi());
        $this->assertEquals(0, $countSuivi->getSignalementNoSuivi());
    }
}
