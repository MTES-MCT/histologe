<?php

namespace App\Tests\Unit\Entity\Enum;

use App\Entity\Enum\ServiceSecoursCategory;
use PHPUnit\Framework\TestCase;

class ServiceSecoursCategoryTest extends TestCase
{
    public function testCasesAndLabels(): void
    {
        $this->assertCount(5, ServiceSecoursCategory::cases());
        $this->assertSame('SDIS', ServiceSecoursCategory::SDIS->label());
        $this->assertSame('Police', ServiceSecoursCategory::POLICE->label());
        $this->assertSame('Gendarmerie', ServiceSecoursCategory::GENDARMERIE->label());
        $this->assertSame('Marins-pompiers', ServiceSecoursCategory::MARINS_POMPIERS->label());
        $this->assertSame('Autre', ServiceSecoursCategory::AUTRE->label());
    }

    public function testTryFromLabel(): void
    {
        $this->assertSame(ServiceSecoursCategory::SDIS, ServiceSecoursCategory::tryFromLabel('SDIS'));
        $this->assertSame(ServiceSecoursCategory::POLICE, ServiceSecoursCategory::tryFromLabel('Police'));
        $this->assertSame(ServiceSecoursCategory::GENDARMERIE, ServiceSecoursCategory::tryFromLabel('Gendarmerie'));
        $this->assertSame(ServiceSecoursCategory::MARINS_POMPIERS, ServiceSecoursCategory::tryFromLabel('Marins-pompiers'));
        $this->assertSame(ServiceSecoursCategory::AUTRE, ServiceSecoursCategory::tryFromLabel('Autre'));
        $this->assertNull(ServiceSecoursCategory::tryFromLabel('Inexistant'));
    }
}
