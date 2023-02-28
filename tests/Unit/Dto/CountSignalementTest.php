<?php

namespace App\Tests\Unit\Dto;

use App\Dto\CountSignalement;
use PHPUnit\Framework\TestCase;

class CountSignalementTest extends TestCase
{
    public function testGetValidCountSignalement(): void
    {
        $countSignalement = new CountSignalement(20, 3, 7, 6, 4);
        $countSignalement
            ->setClosedByAtLeastOnePartner(4)
            ->setAffected(2);

        $this->assertEquals(20, $countSignalement->getTotal());
        $this->assertEquals(3, $countSignalement->getNew());
        $this->assertEquals(7, $countSignalement->getActive());
        $this->assertEquals(6, $countSignalement->getClosed());
        $this->assertEquals(4, $countSignalement->getRefused());
        $this->assertEquals(4, $countSignalement->getClosedByAtLeastOnePartner());
        $this->assertEquals(2, $countSignalement->getAffected());
        $this->assertEquals([
            'new' => 15.0,
            'active' => 35.0,
            'closed' => 30.0,
            'refused' => 20.0,
            ],
            $countSignalement->getPercentage()
        );
    }

    public function testEmptyCountSignalement(): void
    {
        $countSignalement = new CountSignalement(0, 0, 0, 0, 0);
        $this->assertEquals(0, $countSignalement->getTotal());
        $this->assertEquals(0, $countSignalement->getNew());
        $this->assertEquals(0, $countSignalement->getActive());
        $this->assertEquals(0, $countSignalement->getClosed());
        $this->assertEquals([
            'new' => 0,
            'active' => 0,
            'closed' => 0,
            'refused' => 0,
            ],
            $countSignalement->getPercentage()
        );
    }
}
