<?php

namespace App\Tests\Unit\Entity\Enum;

use App\Entity\Enum\ProfileDeclarant;
use PHPUnit\Framework\TestCase;

class ProfileDeclarantTest extends TestCase
{
    public function testGetListWithGroup()
    {
        $list = ProfileDeclarant::getListWithGroup();

        $this->assertCount(9, $list);
    }
}
