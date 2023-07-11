<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    use FixturesHelper;

    public function testCreateUserWithNomComplet(): void
    {
        $user = $this->getUser([User::ROLE_ADMIN_TERRITORY]);
        $this->assertEquals('John', $user->getPrenom());
        $this->assertEquals('Doe', $user->getNom());
        $this->assertEquals('DOE John', $user->getNomComplet());
    }
}
