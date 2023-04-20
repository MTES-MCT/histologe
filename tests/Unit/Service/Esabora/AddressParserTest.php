<?php

namespace App\Tests\Unit\Service\Esabora;

use App\Service\Esabora\AddressParser;
use App\Service\Esabora\Enum\ExtensionAdresse;
use PHPUnit\Framework\TestCase;

class AddressParserTest extends TestCase
{
    /**
     * @dataProvider provideAdresse
     */
    public function testAdressParser(string $address, ?string $number, ?string $suffix, ?string $street): void
    {
        $result = (new AddressParser())->parse($address);
        $this->assertArrayHasKey('number', $result);
        $this->assertArrayHasKey('suffix', $result);
        $this->assertArrayHasKey('street', $result);
        $this->assertEquals($number, $result['number']);
        $this->assertEquals($suffix, $result['suffix']);
        $this->assertEquals($street, $result['street']);
    }

    public function provideAdresse(): \Generator
    {
        yield '141bis Rue du Pdt J Fitzgerald Kennedy' => [
            '141bis Rue du Pdt J Fitzgerald Kennedy',
            '141',
            ExtensionAdresse::BIS->name,
            'Rue du Pdt J Fitzgerald Kennedy'
        ];

        yield '29 Bis Avenue Flores' => [
            '29 Bis Avenue Flores',
            '29',
            ExtensionAdresse::BIS->name,
            'Avenue Flores'
        ];

        yield '4ter Rue de Saint-quentin' => [
            '4ter Rue de Saint-quentin',
            '4',
            ExtensionAdresse::TER->name,
            'Rue de Saint-quentin'
        ];

        yield '20 TER Rue(s) DE CHARTREUSE' => [
            '20 TER Rue(s) DE CHARTREUSE',
            '20',
            ExtensionAdresse::TER->name,
            'Rue(s) DE CHARTREUSE'
        ];

        yield '48 quater Route(s) DE GRENOBLE' => [
            '48 quater Route(s) DE GRENOBLE',
            '48',
            ExtensionAdresse::QUATER->name,
            'Route(s) DE GRENOBLE'
        ];

        yield '12 Rue de la république' => [
            '12 Rue de la république',
            '12',
            null,
            'Rue de la république'
        ];

        yield 'Rue de la république' => [
            'Rue de la république',
            null,
            null,
            'Rue de la république'
        ];

        yield '12 A Rue de la république' => [
            '12 A Rue de la république',
            '12',
            ExtensionAdresse::A->name,
            'Rue de la république'
        ];

        yield '12 B Rue de la république' => [
            '12 B Rue de la république',
            '12',
            ExtensionAdresse::B->name,
            'Rue de la république'
        ];

        yield '12 C Rue de la république' => [
            '12 C Rue de la république',
            '12',
            ExtensionAdresse::C->name,
            'Rue de la république'
        ];

        yield '12 D Rue de la république' => [
            '12 D Rue de la république',
            '12',
            ExtensionAdresse::D->name,
            'Rue de la république'
        ];

        yield '12 Q Rue de la république' => [
            '12 Q Rue de la république',
            '12',
            ExtensionAdresse::Q->name,
            'Rue de la république'
        ];

        yield '12 T Rue de la république' => [
            '12 T Rue de la république',
            '12',
            ExtensionAdresse::T->name,
            'Rue de la république'
        ];

        yield '12 QUINQUIES Rue de la république' => [
            '12 QUINQUIES Rue de la république',
            '12',
            ExtensionAdresse::QUINQUIES->name,
            'Rue de la république'
        ];

        yield '12 SEXIES Rue de la république' => [
            '12 SEXIES Rue de la république',
            '12',
            ExtensionAdresse::SEXIES->name,
            'Rue de la république'
        ];

        yield '12 SEPTIES Rue de la république' => [
            '12 SEPTIES Rue de la république',
            '12',
            ExtensionAdresse::SEPTIES->name,
            'Rue de la république'
        ];

        yield '12 OCTIES Rue de la république' => [
            '12 OCTIES Rue de la république',
            '12',
            ExtensionAdresse::OCTIES->name,
            'Rue de la république'
        ];

        yield '12 NONIES Rue de la république' => [
            '12 NONIES Rue de la république',
            '12',
            ExtensionAdresse::NONIES->name,
            'Rue de la république'
        ];

        yield '12 DECIES Rue de la république' => [
            '12 DECIES Rue de la république',
            '12',
            ExtensionAdresse::DECIES->name,
            'Rue de la république'
        ];
    }
}