<?php

namespace App\Tests\Dto;

use App\Dto\CountSignalement;
use PHPUnit\Framework\TestCase;

class CountSignalementTest extends TestCase
{
    public function testGetValidCountSignalement(): void
    {
        $countSignalement = new CountSignalement(20, 3, 7, 6, 4);
        $this->assertEquals(20, $countSignalement->getTotal());
        $this->assertEquals(3, $countSignalement->getNew());
        $this->assertEquals(7, $countSignalement->getActive());
        $this->assertEquals(6, $countSignalement->getClosed());
        $this->assertEquals(4, $countSignalement->getRefused());
    }

    public function testEmptyCountSignalement(): void
    {
        $countSignalement = new CountSignalement(0, 0, 0, 0, 0);
        $this->assertEquals(0, $countSignalement->getTotal());
        $this->assertEquals(0, $countSignalement->getNew());
        $this->assertEquals(0, $countSignalement->getActive());
        $this->assertEquals(0, $countSignalement->getClosed());
    }
}
