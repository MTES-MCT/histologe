<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Territory;
use PHPUnit\Framework\TestCase;

class TerritoryTest extends TestCase
{
    public function testTerritoryIsStringable(): void
    {
        $territory = (new Territory())
            ->setName('Ain')
            ->setZip('01')
            ->setTimezone('Europe/Paris')
            ->setBbox([])
            ->setIsActive(false);

        $this->assertEquals('Ain', $territory);
    }
}
