<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\DashboardTabPanel;

use App\Entity\Territory;
use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabQueryParameters;
use PHPUnit\Framework\TestCase;

class TabBodyTest extends TestCase
{
    public function testTabBodyInitialization(): void
    {
        $territoryMock = $this->createMock(Territory::class);
        $territories = [$territoryMock];

        $tabQueryParameters = new TabQueryParameters(
            territoireId: 1,
            communeCodePostal: '13002',
            partenairesId: [123, 456],
            sortBy: 'createdAt',
            orderBy: 'ASC'
        );

        $tabBody = new TabBody(
            type: 'type-test',
            template: 'template-test.html.twig',
            territoires: $territories,
            tabQueryParameters: $tabQueryParameters
        );

        $this->assertSame('type-test', $tabBody->getType());
        $this->assertSame('template-test.html.twig', $tabBody->getTemplate());
        $this->assertSame($territories, $tabBody->getTerritoires());
        $this->assertSame($tabQueryParameters, $tabBody->getTabQueryParameters());

        // Test default data value (should be null at initialization)
        $this->assertNull($tabBody->getData());
    }

    public function testSetAndGetData(): void
    {
        $tabBody = new TabBody(type: 'type-test');

        $this->assertNull($tabBody->getData(), 'Initial data should be null.');

        $data = ['item1', 'item2', 'item3'];
        $tabBody->setData($data);

        $this->assertSame($data, $tabBody->getData(), 'getData should return the data that was set.');
    }

    public function testSetAndGetType(): void
    {
        $tabBody = new TabBody(type: 'initial-type');

        $this->assertSame('initial-type', $tabBody->getType());

        $tabBody->setType('updated-type');
        $this->assertSame('updated-type', $tabBody->getType());
    }

    public function testSetAndGetTemplate(): void
    {
        $tabBody = new TabBody(type: 'type-test', template: 'template-initial.html.twig');

        $this->assertSame('template-initial.html.twig', $tabBody->getTemplate());

        $tabBody->setTemplate('template-updated.html.twig');
        $this->assertSame('template-updated.html.twig', $tabBody->getTemplate());
    }
}
