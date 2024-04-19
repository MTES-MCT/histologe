<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Commune;
use PHPUnit\Framework\TestCase;

class CommuneTest extends TestCase
{
    /**
     * @dataProvider provideCommune
     */
    public function testCommunes(string $nomFromDatabase, string $nomCleaned)
    {
        $commune = (new Commune())->setNom($nomFromDatabase);
        $this->assertEquals($nomCleaned, $commune->getNom());
    }

    public function provideCommune(): \Generator
    {
        yield 'Lyon 1er Arrondissement' => ['Lyon 1er Arrondissement', 'Lyon'];
        yield 'Marseille 1er Arrondissement' => ['Marseille 1er Arrondissement', 'Marseille'];
        yield 'Paris 1er Arrondissement' => ['Paris 1er Arrondissement', 'Paris'];
        yield 'Lyon 2e Arrondissement' => ['Lyon 2e Arrondissement', 'Lyon'];
        yield 'Marseille 3e Arrondissement' => ['Marseille 3e Arrondissement', 'Marseille'];
        yield 'Paris 4e Arrondissement' => ['Paris 4e Arrondissement', 'Paris'];
        yield 'Marseille 14e Arrondissement' => ['Marseille 14e Arrondissement', 'Marseille'];
        yield 'Paris 20e Arrondissement' => ['Paris 20e Arrondissement', 'Paris'];
        yield 'Saint-Mars-du-Désert' => ['Saint-Mars-du-Désert', 'Saint-Mars-du-Désert'];
    }
}
