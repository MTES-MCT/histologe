<?php

namespace App\Tests\Dto;

use App\Dto\CountSuivi;
use PHPUnit\Framework\TestCase;

class CountSuiviTest extends TestCase
{
    public function testGetValidCountSuivi(): void
    {
        $countSuivi = new CountSuivi(20.6, 123, 70);
        $this->assertEquals(20.6, $countSuivi->getAverage());
        $this->assertEquals(123, $countSuivi->getPartner());
        $this->assertEquals(70, $countSuivi->getUsager());
    }
}
