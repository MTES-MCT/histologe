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

        yield 'Bâtiment principal' => ['Bâtiment principal', null];

        yield '4e etage gauche' => ['4e etage gauche', 4];

        yield '2emes' => ['2emes', 2];

        yield 'Petite cours' => ['Petite cours', null];

        yield 'RDC  entrée 10' => ['RDC  entrée 10', 0];

        yield '1 / 2 rdc' => ['1 / 2 rdc', 0];

        yield '4ème étage - entrée 1' => ['4ème étage - entrée 1', 4];

        yield 'Dernière maison à gauche' => ['Dernière maison à gauche', null];

        yield 'Rez de chaussée' => ['Rez de chaussée', 0];

        yield 'REZ DE CHAUSSEE' => ['REZ DE CHAUSSEE', 0];
        yield 'rdc gauche' => ['rdc gauche', 0];
        yield 'RDC gauche' => ['RDC gauche', 0];
        yield 'RDC' => ['RDC', 0];
        yield 'rc' => ['rc', 0];
        yield 'Le Val de Provence 1' => ['Le Val de Provence 1', 1];
        yield 'étage 1' => ['étage 1', 1];
        yield '4 ème étage' => ['4 ème étage', 4];
        yield '3ème étage' => ['3ème étage', 3];
        yield '3ème' => ['3ème', 3];
        yield '2ème étage' => ['2ème étage', 2];
        yield '2 porte droite' => ['2 porte droite', 2];
        yield '1er étage' => ['1er étage', 1];
        yield '1er' => ['1er', 1];
        yield 'Sous sol' => ['Sous sol', -1];
        yield 'hôtel grac porte 11' => ['hôtel grac porte 11', 11]; // idéalement devrait renvoyer null, mais difficile à mettre en place
    }
}
