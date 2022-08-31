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

    public function testEmptyPostalCode(): void
    {
        $result = $this->postalCodeHomeCheckerService->getRedirection('');
        $this->assertFalse($result);
    }

    public function testNullPostalCode(): void
    {
        $result = $this->postalCodeHomeCheckerService->getRedirection('null');
        $this->assertFalse($result);
    }

    public function testNotExistingPostalCode(): void
    {
        $result = $this->postalCodeHomeCheckerService->getRedirection('57070');
        $this->assertFalse($result);
    }

    public function testExistingPostalCodeInListDirect1(): void
    {
        $result = $this->postalCodeHomeCheckerService->getRedirection('04000');
        $this->assertIsString($result);
        $this->assertStringContainsString('signalement', $result);
    }

    public function testExistingPostalCodeInListDirect2(): void
    {
        $result = $this->postalCodeHomeCheckerService->getRedirection('04100');
        $this->assertIsString($result);
        $this->assertStringContainsString('signalement', $result);
    }

    public function testExistingPostalCodeInListSublist1(): void
    {
        $result = $this->postalCodeHomeCheckerService->getRedirection('59000');
        $this->assertIsString($result);
        $this->assertStringContainsString('signalement', $result);
    }

    public function testExistingPostalCodeInListSublist2(): void
    {
        $result = $this->postalCodeHomeCheckerService->getRedirection('59100');
        $this->assertIsString($result);
        $this->assertStringContainsString('signalement', $result);
    }

    public function testExistingPostalCodeInDB1(): void
    {
        $result = $this->postalCodeHomeCheckerService->getRedirection('01000');
        $this->assertIsString($result);
        $this->assertStringContainsString('local', $result);
    }

    public function testExistingPostalCodeInDB2(): void
    {
        $result = $this->postalCodeHomeCheckerService->getRedirection('01100');
        $this->assertIsString($result);
        $this->assertStringContainsString('local', $result);
    }
}
