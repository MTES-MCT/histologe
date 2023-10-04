<?php

namespace App\Tests\Unit\Utils;

use App\Utils\EscalierParser;
use PHPUnit\Framework\TestCase;

class EscalierParserTest extends TestCase
{
    /**
     * @dataProvider provideEscalier
     */
    public function testEscalierParser(?string $currentEscalierValue, string|null $EscalierParsed): void
    {
        $this->assertEquals($EscalierParsed, EscalierParser::parse($currentEscalierValue));
    }

    public function provideEscalier(): \Generator
    {
        yield '-2' => ['-2', '-2'];
        yield '0' => ['0', '0'];
        yield '1' => ['1', '1'];
        yield '2' => ['2', '2'];
        yield '3' => ['3', '3'];
        yield '30' => ['30', '30'];
        yield '221' => ['221', '221'];
        yield '492720522' => ['492720522', null];
        yield ' Logement ADOMA' => [' Logement ADOMA', null];
        yield ' porte Droite' => [' porte Droite', 'DRO'];
        yield '(escalier extérieur metallique) ' => ['(escalier extérieur metallique) ', 'EXT'];
        yield '1 Bat 3' => ['1 Bat 3', '3'];
        yield '140 BLOC B' => ['140 BLOC B', 'B'];
        yield '2b' => ['2b', '2B'];
        yield '7C' => ['7C', '7C'];
        yield 'A-2' => ['A-2', 'A-2'];
        yield 'allée N2' => ['allée N2', 'N2'];
        yield 'angelslizaga@gmail.com' => ['angelslizaga@gmail.com', null];
        yield 'B1 escalier 3' => ['B1 escalier 3', '3'];
        yield 'bât 1' => ['bât 1', '1'];
        yield 'Bat 1a' => ['Bat 1a', '1A'];
        yield 'Batiment 19' => ['Batiment 19', '19'];
        yield 'Bâtiment 2' => ['Bâtiment 2', '2'];
        yield 'Bloc B' => ['Bloc B', 'B'];
        yield 'BT 6' => ['BT 6', '6'];
        yield 'côté rue' => ['côté rue', null];
        yield 'Entrée 7' => ['Entrée 7', '7'];
        yield 'ENTREE K ' => ['ENTREE K ', 'K'];
        yield 'Esc 12 bat 4' => ['Esc 12 bat 4', '12'];
        yield 'escalier extérieur' => ['escalier extérieur', 'EXT'];
        yield 'Immeuble émeraude BT A' => ['Immeuble émeraude BT A', 'A'];
        yield 'L5B' => ['L5B', 'L5B'];
        yield 'La Pauline' => ['La Pauline', null];
        yield 'porte 27' => ['porte 27', '27'];
        yield 'Rdc' => ['Rdc', 'RDC'];
        yield 'Immeuble de fond n°14A' => ['Immeuble de fond n° 14A', '14A'];
        yield 'n°7' => ['n°7', '7'];
        yield 'dernier etage entree 58' => ['dernier etage entree 58', '58'];
        yield 'Bâtiment 4 escalier 2' => ['Bâtiment 4 escalier 2', '2'];
        yield 'Bt 11 esc 17 ' => ['Bt 11 esc 17 ', '17'];
        yield '1 escalier ' => ['1 escalier ', '1'];
        yield '1ER escalier ' => ['1ER escalier ', '1'];
        yield '2ème escalier ' => ['2ème escalier ', '2'];
    }
}
