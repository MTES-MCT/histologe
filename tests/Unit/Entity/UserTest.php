<?php

namespace App\Tests\Unit\Entity;

use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    use FixturesHelper;

    public function testCreateUserWithFullname(): void
    {
        $user = $this->getUser();
        $this->assertEquals('John Doe', $user->getFullname());
    }
}
