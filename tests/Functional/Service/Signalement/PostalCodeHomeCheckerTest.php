<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Service\Signalement\PostalCodeHomeChecker;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PostalCodeHomeCheckerTest extends KernelTestCase
{
    private $postalCodeHomeChecker;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->postalCodeHomeChecker = $container->get(PostalCodeHomeChecker::class);
    }

    public function testActiveTerritory(): void
    {
        $this->assertTrue($this->postalCodeHomeChecker->isActive('01270'));
    }

    public function testActiveCorseTerritory(): void
    {
        $this->assertTrue($this->postalCodeHomeChecker->isActive('20151'));
    }

    public function testInactiveTerritory(): void
    {
        $this->assertFalse($this->postalCodeHomeChecker->isActive('75001'));
    }
}
