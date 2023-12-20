<?php

namespace App\Tests\Unit\Service\DataGouv;

use App\Service\DataGouv\AddressService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class AddressServiceTest extends TestCase
{
    private AddressService $addressService;

    protected function setUp(): void
    {
        $mockResponse = new MockResponse(
            file_get_contents(__DIR__.'/../../../files/datagouv/get_api_ban_item_response.json')
        );
        $mockHttpClient = new MockHttpClient($mockResponse);
        $this->addressService = new AddressService($mockHttpClient, $this->createMock(LoggerInterface::class));
    }

    public function testGetCodeInsee(): void
    {
        $codeInsee = $this->addressService->getCodeInsee('8 La Bodinière 44850 Saint-Mars du Désert');
        $this->assertEquals('44179', $codeInsee);
    }

    public function testSearchAddress(): void
    {
        $addresses = $this->addressService->searchAddress('8 La Bodinière 44850 Saint-Mars du Désert');
        $this->assertIsArray($addresses);
        $this->assertArrayHasKey('features', $addresses);
        $this->assertArrayHasKey('attribution', $addresses);
        $this->assertEquals('BAN', $addresses['attribution']);
    }

    public function testGetAddressResponse(): void
    {
        $address = $this->addressService->getAddress('8 La Bodinière 44850 Saint-Mars du Désert');

        $addressComputed = sprintf('%s %s %s', $address->getStreet(), $address->getZipCode(), $address->getCity());
        $this->assertTrue($address->getInseeCode() !== $address->getZipCode());
        $this->assertTrue($address->getLabel() === $addressComputed);
        $this->assertNotEmpty($address->getLongitude());
        $this->assertNotEmpty($address->getLatitude());
        $this->assertArrayHasKey('lat', $address->getGeoloc());
        $this->assertArrayHasKey('lng', $address->getGeoloc());
    }
}
