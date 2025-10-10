<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Repository\TerritoryRepository;
use App\Service\Signalement\PostalCodeHomeChecker;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PostalCodeHomeCheckerTest extends KernelTestCase
{
    private ?PostalCodeHomeChecker $postalCodeHomeChecker;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->postalCodeHomeChecker = $container->get(PostalCodeHomeChecker::class);
    }

    public function testActiveTerritory(): void
    {
        $this->assertTrue($this->postalCodeHomeChecker->isActiveByPostalCode('01270'));
    }

    public function testActiveCorseTerritory(): void
    {
        $this->assertTrue($this->postalCodeHomeChecker->isActiveByPostalCode('20151'));
    }

    public function testActiveCotedorTerritory(): void
    {
        $this->assertTrue($this->postalCodeHomeChecker->isActiveByPostalCode('21000'));
    }

    public function testActiveTerritoryDifferentZip(): void
    {
        $this->assertTrue($this->postalCodeHomeChecker->isActiveByInseeCode('04234'));
    }

    public function testInactiveTerritory(): void
    {
        $this->assertFalse($this->postalCodeHomeChecker->isActiveByPostalCode('75001'));
    }

    public function testInactiveTerritoryDifferentZip(): void
    {
        $this->assertFalse($this->postalCodeHomeChecker->isActiveByInseeCode('75002'));
    }

    public function testTerritoryWithNoAuthorizedInseeCodes(): void
    {
        $territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $territory = $territoryRepository->findOneBy(['id' => 1]);
        $this->assertTrue($this->postalCodeHomeChecker->isAuthorizedInseeCode($territory, '01000'));
    }

    public function testTerritoryWithAuthorizedInseeCodesAndInseeCodeValid(): void
    {
        $territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $territory = $territoryRepository->findOneBy(['id' => 70]);
        $territory->setAuthorizedCodesInsee(['69091']);
        $this->assertTrue($this->postalCodeHomeChecker->isAuthorizedInseeCode($territory, '69091'));
    }

    public function testTerritoryWithAuthorizedInseeCodesAndInseeCodeNotValid(): void
    {
        $territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $territory = $territoryRepository->findOneBy(['id' => 70]);
        $territory->setAuthorizedCodesInsee(['69091']);
        $this->assertFalse($this->postalCodeHomeChecker->isAuthorizedInseeCode($territory, '69092'));
    }

    public function normalizeInseeCode(): void
    {
        $this->assertEquals('13201', $this->postalCodeHomeChecker->normalizeInseeCode('13001', '13055'));
        $this->assertEquals('69381', $this->postalCodeHomeChecker->normalizeInseeCode('69001', '69381'));
        $this->assertEquals('13202', $this->postalCodeHomeChecker->normalizeInseeCode('13002', '13202'));
    }
}
