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
        $this->assertTrue($this->postalCodeHomeChecker->isActive('01270'));
    }

    public function testActiveCorseTerritory(): void
    {
        $this->assertTrue($this->postalCodeHomeChecker->isActive('20151'));
    }

    public function testActiveCotedorTerritory(): void
    {
        $this->assertTrue($this->postalCodeHomeChecker->isActive('21000'));
    }

    public function testInactiveTerritory(): void
    {
        $this->assertFalse($this->postalCodeHomeChecker->isActive('75001'));
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
}
