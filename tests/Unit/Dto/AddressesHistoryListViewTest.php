<?php

namespace App\Tests\Unit\Dto;

use App\Dto\AddressesHistoryListView;
use App\Dto\AddressesHistorySignalementView;
use PHPUnit\Framework\TestCase;

class AddressesHistoryListViewTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $addressesHistory = new AddressesHistoryListView(
            address: '123 rue de la paix',
            cp: '13001',
            ville: 'Marseille',
            territoryId: 42,
            addressForHuman: '123 Rue de la Paix',
            communeForHuman: 'Marseille (13001)',
            lat: '43.2965',
            lng: '5.3698',
            signalements: [],
            arretes: []
        );

        $this->assertSame('123 rue de la paix', $addressesHistory->getAddress());
        $this->assertSame('13001', $addressesHistory->getCp());
        $this->assertSame('Marseille', $addressesHistory->getVille());
        $this->assertSame(42, $addressesHistory->getTerritoryId());
        $this->assertSame('123 Rue de la Paix', $addressesHistory->getAddressForHuman());
        $this->assertSame('Marseille (13001)', $addressesHistory->getCommuneForHuman());
        $this->assertSame('43.2965', $addressesHistory->getLat());
        $this->assertSame('5.3698', $addressesHistory->getLng());
        $this->assertSame([], $addressesHistory->getSignalements());
        $this->assertSame([], $addressesHistory->getArretes());
    }

    public function testConstructorWithNullValues(): void
    {
        $addressesHistory = new AddressesHistoryListView();

        $this->assertNull($addressesHistory->getAddress());
        $this->assertNull($addressesHistory->getCp());
        $this->assertNull($addressesHistory->getVille());
        $this->assertNull($addressesHistory->getTerritoryId());
        $this->assertNull($addressesHistory->getAddressForHuman());
        $this->assertNull($addressesHistory->getCommuneForHuman());
        $this->assertNull($addressesHistory->getLat());
        $this->assertNull($addressesHistory->getLng());
        $this->assertNull($addressesHistory->getSignalements());
        $this->assertNull($addressesHistory->getArretes());
    }

    public function testSetLatAndLng(): void
    {
        $addressesHistory = new AddressesHistoryListView();

        $addressesHistory->setLat('43.2965');
        $addressesHistory->setLng('5.3698');

        $this->assertSame('43.2965', $addressesHistory->getLat());
        $this->assertSame('5.3698', $addressesHistory->getLng());
    }

    public function testSetLatAndLngWithNull(): void
    {
        $addressesHistory = new AddressesHistoryListView(
            lat: '43.2965',
            lng: '5.3698',
        );

        $addressesHistory->setLat(null);
        $addressesHistory->setLng(null);

        $this->assertNull($addressesHistory->getLat());
        $this->assertNull($addressesHistory->getLng());
    }

    public function testAddSignalement(): void
    {
        $addressesHistory = new AddressesHistoryListView();
        $signalement = new AddressesHistorySignalementView(
            url: 'https://example.com/signalement/1',
            ref: '2024-1',
            usager: 'John Doe',
            statut: 'Nouveau'
        );

        $this->assertNull($addressesHistory->getSignalements());

        $addressesHistory->addSignalement($signalement);

        $signalements = $addressesHistory->getSignalements();
        $this->assertIsArray($signalements);
        $this->assertCount(1, $signalements);
        $this->assertSame($signalement, $signalements[0]);
    }

    public function testAddMultipleSignalements(): void
    {
        $addressesHistory = new AddressesHistoryListView();

        $signalement1 = new AddressesHistorySignalementView(
            url: 'https://example.com/signalement/1',
            ref: '2024-1',
            usager: 'John Doe',
            statut: 'Nouveau'
        );

        $signalement2 = new AddressesHistorySignalementView(
            url: 'https://example.com/signalement/2',
            ref: '2024-2',
            usager: 'Jane Smith',
            statut: 'En cours'
        );

        $addressesHistory->addSignalement($signalement1);
        $addressesHistory->addSignalement($signalement2);

        $signalements = $addressesHistory->getSignalements();
        $this->assertCount(2, $signalements);
        $this->assertSame($signalement1, $signalements[0]);
        $this->assertSame($signalement2, $signalements[1]);
    }

    public function testAddArrete(): void
    {
        $addressesHistory = new AddressesHistoryListView();
        $arrete = [
            'id' => 1,
            'dateArrete' => '2024-01-15',
            'typeArrete' => 'interdiction',
        ];

        $this->assertNull($addressesHistory->getArretes());

        $addressesHistory->addArrete($arrete);

        $arretes = $addressesHistory->getArretes();
        $this->assertIsArray($arretes);
        $this->assertCount(1, $arretes);
        $this->assertSame($arrete, $arretes[0]);
    }

    public function testAddMultipleArretes(): void
    {
        $addressesHistory = new AddressesHistoryListView();

        $arrete1 = [
            'id' => 1,
            'dateArrete' => '2024-01-15',
            'typeArrete' => 'interdiction',
        ];

        $arrete2 = [
            'id' => 2,
            'dateArrete' => '2024-02-20',
            'typeArrete' => 'peril',
        ];

        $addressesHistory->addArrete($arrete1);
        $addressesHistory->addArrete($arrete2);

        $arretes = $addressesHistory->getArretes();
        $this->assertCount(2, $arretes);
        $this->assertSame($arrete1, $arretes[0]);
        $this->assertSame($arrete2, $arretes[1]);
    }

    public function testAddSignalementPreservesExistingArray(): void
    {
        $existingSignalement = new AddressesHistorySignalementView(
            url: 'https://example.com/signalement/1',
            ref: '2024-1',
            usager: 'John Doe',
            statut: 'Nouveau'
        );

        $addressesHistory = new AddressesHistoryListView(
            signalements: [$existingSignalement]
        );

        $newSignalement = new AddressesHistorySignalementView(
            url: 'https://example.com/signalement/2',
            ref: '2024-2',
            usager: 'Jane Smith',
            statut: 'En cours'
        );

        $addressesHistory->addSignalement($newSignalement);

        $signalements = $addressesHistory->getSignalements();
        $this->assertCount(2, $signalements);
    }

    public function testAddArretePreservesExistingArray(): void
    {
        $existingArrete = [
            'id' => 1,
            'dateArrete' => '2024-01-15',
            'typeArrete' => 'interdiction',
        ];

        $addressesHistory = new AddressesHistoryListView(
            arretes: [$existingArrete]
        );

        $newArrete = [
            'id' => 2,
            'dateArrete' => '2024-02-20',
            'typeArrete' => 'peril',
        ];

        $addressesHistory->addArrete($newArrete);

        $arretes = $addressesHistory->getArretes();
        $this->assertCount(2, $arretes);
    }

    public function testConstants(): void
    {
        $this->assertSame('||', AddressesHistoryListView::SEPARATOR_CONCAT);
        $this->assertSame(';', AddressesHistoryListView::SEPARATOR_GROUP_CONCAT);
        $this->assertSame(30, AddressesHistoryListView::MAX_LIST_PAGINATION);
    }
}
