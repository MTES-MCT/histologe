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

    public function testGetZipCodeTerritory(): void
    {
        $this->assertEquals(
            PostalCodeHomeChecker::CORSE_DU_SUD_CODE_DEPARTMENT_2A,
            $this->postalCodeHomeChecker->getZipCode('20167'),
        );
        $this->assertEquals(
            PostalCodeHomeChecker::CORSE_DU_SUD_CODE_DEPARTMENT_2A,
            $this->postalCodeHomeChecker->getZipCode('20000'),
        );
        $this->assertEquals(
            PostalCodeHomeChecker::HAUTE_CORSE_CODE_DEPARTMENT_2B,
            $this->postalCodeHomeChecker->getZipCode('20200')
        );
        $this->assertEquals(
            PostalCodeHomeChecker::HAUTE_CORSE_CODE_DEPARTMENT_2B,
            $this->postalCodeHomeChecker->getZipCode('20600')
        );
        $this->assertEquals(
            PostalCodeHomeChecker::LA_REUNION_CODE_DEPARTMENT_974,
            $this->postalCodeHomeChecker->getZipCode('97400')
        );
        $this->assertEquals(
            PostalCodeHomeChecker::MARTNIQUE_CODE_DEPARTMENT_972,
            $this->postalCodeHomeChecker->getZipCode('97200')
        );
        $this->assertEquals(
            '13',
            $this->postalCodeHomeChecker->getZipCode('13002')
        );
    }
}
