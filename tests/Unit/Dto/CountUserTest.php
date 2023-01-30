<?php

namespace App\Tests\Dto;

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
}
