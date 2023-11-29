<?php

namespace App\Tests\Functional\Service\DataGouv;

use App\Service\DataGouv\AddressService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AddressServiceTest extends KernelTestCase
{
    private ?AddressService $addressService;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->addressService = $container->get(AddressService::class);
    }

    public function testGetCodeInsee(): void
    {
        $codeInsee = $this->addressService->getCodeInsee('8 La Bodinière 44850 Saint-Mars du Désert');
        $this->assertEquals('44179', $codeInsee);
    }

    public function testSearchAddress(): void
    {
        $addresses = $this->addressService->searchAddress('2 rue Mars');
        $this->assertIsArray($addresses);
        $this->assertArrayHasKey('features', $addresses);
        $this->assertArrayHasKey('attribution', $addresses);
        $this->assertEquals('BAN', $addresses['attribution']);
    }

    public function testGetAddressResponse(): void
    {
        $address = $this->addressService->getAddress('2 rue Mars Maisons-Alfort');

        $addressComputed = sprintf('%s %s %s', $address->getStreet(), $address->getZipCode(), $address->getCity());
        $this->assertTrue($address->getInseeCode() !== $address->getZipCode());
        $this->assertTrue($address->getLabel() === $addressComputed);
        $this->assertNotEmpty($address->getLongitude());
        $this->assertNotEmpty($address->getLatitude());
    }
}
