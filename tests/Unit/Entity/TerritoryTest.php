<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Territory;
use PHPUnit\Framework\TestCase;

class TerritoryTest extends TestCase
{
    public function testTerritoryIsStringable()
    {
        $territory = (new Territory())
            ->setName('Ain')
            ->setZip('01')
            ->setBbox([])
            ->setIsActive(false);

        $this->assertEquals('Ain', $territory);
    }
}
