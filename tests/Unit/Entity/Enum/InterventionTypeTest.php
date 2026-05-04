<?php

namespace App\Tests\Unit\Entity\Enum;

use App\Entity\Enum\InterventionType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InterventionTypeTest extends TestCase
{
    #[DataProvider('provideInterventionType')]
    public function testFromValidLabel(string $label, InterventionType $interventionType): void
    {
        $intervention = InterventionType::tryFromLabel($label);

        $this->assertEquals($interventionType, $intervention);
    }

    public function testFromInvalidLabel(): void
    {
        $intervention = InterventionType::tryFromLabel('Type de visite invalide');
        $this->assertNull($intervention);
    }

    public static function provideInterventionType(): \Generator
    {
        yield 'Visite contrôle' => ['Visite contrôle', InterventionType::VISITE_CONTROLE];
        yield 'Visite de contrôle' => ['Visite de contrôle', InterventionType::VISITE_CONTROLE];
        yield 'Visite' => ['Visite', InterventionType::VISITE];
        yield 'Arrêté préfectoral' => ['Arrêté préfectoral', InterventionType::ARRETE_PREFECTORAL];
    }
}
