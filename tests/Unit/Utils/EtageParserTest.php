<?php

namespace App\Tests\Unit\Utils;

use App\Utils\EtageParser;
use PHPUnit\Framework\TestCase;

class EtageParserTest extends TestCase
{
    /**
     * @dataProvider provideEtage
     */
    public function testEtageParser(?string $currentEtageValue, ?int $etageParsed): void
    {
        $this->assertEquals($etageParsed, EtageParser::parse($currentEtageValue));
    }

    public function provideEtage(): \Generator
    {
        yield '3 ème étage porte à droite' => ['3 ème étage porte à droite', 3];

        yield '3 EME ETAGE APPARTEMENT B329' => ['3 EME ETAGE APPARTEMENT B329', 3];

        yield 'Bâtiment C1' => ['Bâtiment C', null];

        yield 'ETAGE 5' => ['ETAGE 5', 5];

        yield '1er - 2ème - 3ème - 4ème' => ['1er - 2ème - 3ème - 4ème', 1];

        yield 'Annexe du bâtiment n° 51 rue Jean - Augustin Asselin, rdc' => [
            'Annexe du bâtiment n° 51 rue Jean - Augustin Asselin, rdc',
            0,
        ];

        yield 'Bâtiment principal' => ['Bâtiment principal', 0];

        yield '4e etage gauche' => ['4e etage gauche', 4];

        yield '2emes' => ['2emes', 2];

        yield 'Petite cours' => ['Petite cours', null];

        yield 'RDC  entrée 10' => ['RDC  entrée 10', 0];

        yield '1 / 2 rdc' => ['1 / 2 rdc', 0];

        yield '4ème étage - entrée 1' => ['4ème étage - entrée 1', 4];

        yield 'Dernière maison à gauche' => ['Dernière maison à gauche', null];

        yield 'null' => [null, null];
    }
}
