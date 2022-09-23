<?php

namespace App\Tests\Functional\Service;

use App\Service\PostalCodeHomeCheckerService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PostalCodeHomeCheckerServiceTest extends KernelTestCase
{
    private $postalCodeHomeCheckerService;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->postalCodeHomeCheckerService = $container->get(PostalCodeHomeCheckerService::class);
    }

    public function testActiveTerritory(): void
    {
        $this->assertTrue($this->postalCodeHomeCheckerService->isActive('01270'));
    }

    public function testInactiveTerritory(): void
    {
        $this->assertFalse($this->postalCodeHomeCheckerService->isActive('75001'));
    }
}
