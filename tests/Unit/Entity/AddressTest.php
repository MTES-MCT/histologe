<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Address;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{
    #[DataProvider('provideAddressData')]
    public function testGetFull(Address $address, string $expectedFullAddress): void
    {
        $this->assertEquals($expectedFullAddress, $address->getFull());
    }

    public static function provideAddressData(): \Generator
    {
        yield 'Address with housenumber' => [
            new Address()
                ->setHousenumber('17')
                ->setStreet('rue Désirée Clary')
                ->setPostCode('13002')
                ->setCity('Marseille'),
            '17 rue Désirée Clary 13002 Marseille',
        ];

        yield 'Address without housenumber' => [
            new Address()
                ->setStreet('square de la rouguière')
                ->setPostCode('13011')
                ->setCity('Marseille'),
            'square de la rouguière 13011 Marseille',
        ];
    }
}
