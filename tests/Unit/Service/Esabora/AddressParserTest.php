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
            'Rue du Pdt J Fitzgerald Kennedy',
        ];

        yield '29 Bis Avenue Flores' => [
            '29 Bis Avenue Flores',
            '29',
            ExtensionAdresse::BIS->name,
            'Avenue Flores',
        ];

        yield '4ter Rue de Saint-quentin' => [
            '4ter Rue de Saint-quentin',
            '4',
            ExtensionAdresse::TER->name,
            'Rue de Saint-quentin',
        ];

        yield '20 TER Rue(s) DE CHARTREUSE' => [
            '20 TER Rue(s) DE CHARTREUSE',
            '20',
            ExtensionAdresse::TER->name,
            'Rue(s) DE CHARTREUSE',
        ];

        yield '48 quater Route(s) DE GRENOBLE' => [
            '48 quater Route(s) DE GRENOBLE',
            '48',
            ExtensionAdresse::QUATER->name,
            'Route(s) DE GRENOBLE',
        ];

        yield '12 boulevard de la république' => [
            '12 Boulevard de la république',
            '12',
            null,
            'Boulevard de la république',
        ];

        yield 'Avenue de la joliette' => [
            'Avenue de la joliette',
            null,
            null,
            'Avenue de la joliette',
        ];

        yield '12 A Square de la france' => [
            '12 A Square de la france',
            '12',
            ExtensionAdresse::A->name,
            'Square de la france',
        ];

        yield '12 B Quai de la roumanie' => [
            '12 B Quai de la roumanie',
            '12',
            ExtensionAdresse::B->name,
            'Quai de la roumanie',
        ];

        yield '12 C Impasse de la jordanie' => [
            '12 C Impasse de la jordanie',
            '12',
            ExtensionAdresse::C->name,
            'Impasse de la jordanie',
        ];

        yield '12 D Allée de la mer' => [
            '12 D Allée de la mer',
            '12',
            ExtensionAdresse::D->name,
            'Allée de la mer',
        ];

        yield '12 Q Chemin de la brise' => [
            '12 Q Chemin de la brise',
            '12',
            ExtensionAdresse::Q->name,
            'Chemin de la brise',
        ];

        yield '12 T Chaussée de la table' => [
            '12 T Chaussée de la table',
            '12',
            ExtensionAdresse::T->name,
            'Chaussée de la table',
        ];

        yield '12 QUINQUIES Cours de la rivière' => [
            '12 QUINQUIES Cours de la rivière',
            '12',
            ExtensionAdresse::QUINQUIES->name,
            'Cours de la rivière',
        ];

        yield '12 SEXIES Place de la sorbonne' => [
            '12 SEXIES Place de la sorbonne',
            '12',
            ExtensionAdresse::SEXIES->name,
            'Place de la sorbonne',
        ];

        yield '12 SEPTIES Montée de la poire' => [
            '12 SEPTIES Montée de la poire',
            '12',
            ExtensionAdresse::SEPTIES->name,
            'Montée de la poire',
        ];

        yield '12 OCTIES Passage de la pomme' => [
            '12 OCTIES Passage de la pomme',
            '12',
            ExtensionAdresse::OCTIES->name,
            'Passage de la pomme',
        ];

        yield '12 NONIES Rond-point de la pastorale' => [
            '12 NONIES Rond-point de la pastorale',
            '12',
            ExtensionAdresse::NONIES->name,
            'Rond-point de la pastorale',
        ];

        yield '12 DECIES Parvis de la coupe' => [
            '12 DECIES Parvis de la coupe',
            '12',
            ExtensionAdresse::DECIES->name,
            'Parvis de la coupe',
        ];
    }
}
