<?php

namespace App\Tests\Unit\Dto;

use App\Dto\CountUser;
use PHPUnit\Framework\TestCase;

class CountUserTest extends TestCase
{
    public function testGetValidCountUser(): void
    {
        $countUser = new CountUser(100, 23);
        $this->assertEquals(100, $countUser->getActive());
        $this->assertEquals(23, $countUser->getInactive());
    }

    public function testEmptyCountUser(): void
    {
        $countUser = new CountUser(0, 0);
        $this->assertEquals(0, $countUser->getActive());
        $this->assertEquals(0, $countUser->getInactive());
    }
}
