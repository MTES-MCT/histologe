<?php

namespace App\Tests\Unit\Service\ServiceSecours;

use App\Entity\Enum\ServiceSecoursCategory;
use App\Service\ServiceSecours\ServiceSecoursCategoryResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ServiceSecoursCategoryResolverTest extends TestCase
{
    private ServiceSecoursCategoryResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ServiceSecoursCategoryResolver();
    }

    #[DataProvider('provideOrganizationalUnits')]
    public function testResolve(string $organizationalUnit, ServiceSecoursCategory $expectedCategory): void
    {
        $this->assertSame($expectedCategory, $this->resolver->resolve($organizationalUnit));
    }

    public static function provideOrganizationalUnits(): \Generator
    {
        yield 'BMPM' => ['BMPM - Bataillon de marins-pompiers', ServiceSecoursCategory::MARINS_POMPIERS];
        yield 'Marin' => ['Marins pompiers de Marseille', ServiceSecoursCategory::MARINS_POMPIERS];
        yield 'Gendarmerie' => ['Groupement de gendarmerie départementale', ServiceSecoursCategory::GENDARMERIE];
        yield 'Gendarme' => ['Gendarme adjoint', ServiceSecoursCategory::GENDARMERIE];
        yield 'Police' => ['Hôtel de Police', ServiceSecoursCategory::POLICE];
        yield 'Commissariat' => ['Commissariat central', ServiceSecoursCategory::POLICE];
        yield 'CRS' => ['CRS 53', ServiceSecoursCategory::POLICE];
        yield 'SDIS' => ['SDIS 13', ServiceSecoursCategory::SDIS];
        yield 'Pompier' => ['Centre de secours des pompiers', ServiceSecoursCategory::SDIS];
        yield 'Sapeur' => ['Sapeurs-pompiers', ServiceSecoursCategory::SDIS];
        yield 'Secours' => ['Poste de secours', ServiceSecoursCategory::SDIS];
        yield 'Incendie' => ['Service départemental d\'incendie', ServiceSecoursCategory::SDIS];
        yield 'Feu' => ['Brigade du feu', ServiceSecoursCategory::SDIS];
        yield 'Autre' => ['Mairie de Paris', ServiceSecoursCategory::AUTRE];
    }
}
