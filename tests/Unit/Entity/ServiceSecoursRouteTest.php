<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Enum\ServiceSecoursCategory;
use App\Entity\ServiceSecoursRoute;
use App\Entity\Territory;
use PHPUnit\Framework\TestCase;

class ServiceSecoursRouteTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $territory = new Territory();
        $territory->setName('Bouches-du-Rhône');

        $route = new ServiceSecoursRoute();
        $route->setName('SDIS 13')
            ->setEmail('sdis13@example.com')
            ->setPhone('0491000001')
            ->setTerritory($territory)
            ->setCategory(ServiceSecoursCategory::SDIS);

        $this->assertSame('SDIS 13', $route->getName());
        $this->assertSame('SDIS-13', $route->getSlug());
        $this->assertSame('sdis13@example.com', $route->getEmail());
        $this->assertSame('0491000001', $route->getPhone());
        $this->assertSame($territory, $route->getTerritory());
        $this->assertSame(ServiceSecoursCategory::SDIS, $route->getCategory());
        $this->assertNotNull($route->getUuid());
    }
}
