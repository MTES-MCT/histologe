<?php

namespace App\Tests\Unit\Factory;

use App\Dto\AddressesHistoryListView;
use App\Dto\AddressesHistorySignalementView;
use App\Entity\Enum\SignalementStatus;
use App\Factory\AddressesHistoryListViewFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AddressesHistoryListViewFactoryTest extends KernelTestCase
{
    private AddressesHistoryListViewFactory $factory;
    private UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $this->factory = new AddressesHistoryListViewFactory($this->urlGenerator);
    }

    public function testCreateInstance(): void
    {
        $addressOccupant = '123 rue de la paix';
        $cpOccupant = '13001';
        $villeOccupant = 'Marseille';
        $territoryId = 42;
        $addressForHuman = '123 Rue de la Paix';
        $communeForHuman = 'Marseille (13001)';

        $result = $this->factory->createInstance(
            addressOccupant: $addressOccupant,
            cpOccupant: $cpOccupant,
            villeOccupant: $villeOccupant,
            territoryId: $territoryId,
            addressForHuman: $addressForHuman,
            communeForHuman: $communeForHuman
        );

        $this->assertInstanceOf(AddressesHistoryListView::class, $result);
        $this->assertSame($addressOccupant, $result->getAddress());
        $this->assertSame($cpOccupant, $result->getCp());
        $this->assertSame($villeOccupant, $result->getVille());
        $this->assertSame($territoryId, $result->getTerritoryId());
        $this->assertSame($addressForHuman, $result->getAddressForHuman());
        $this->assertSame($communeForHuman, $result->getCommuneForHuman());
        $this->assertNull($result->getLat());
        $this->assertNull($result->getLng());
        $this->assertNull($result->getSignalements());
        $this->assertNull($result->getArretes());
    }

    public function testCreateSignalementInstanceFromSignalementData(): void
    {
        $data = [
            'signalementUuid' => 'abc123-def456-ghi789',
            'reference' => '2024-001',
            'prenomOccupant' => 'John',
            'nomOccupant' => 'Doe',
            'statut' => SignalementStatus::ACTIVE,
        ];

        $result = $this->factory->createSignalementInstanceFromSignalementData($data);

        $this->assertInstanceOf(AddressesHistorySignalementView::class, $result);
        $this->assertStringContainsString('/bo/signalements/abc123-def456-ghi789', $result->getUrl());
        $this->assertStringStartsWith('http', $result->getUrl());
        $this->assertSame('2024-001', $result->getRef());
        $this->assertSame('John Doe', $result->getUsager());
        $this->assertSame('en cours', $result->getStatut());
    }

    public function testCreateSignalementInstanceWithDifferentStatut(): void
    {
        $data = [
            'signalementUuid' => 'xyz789',
            'reference' => '2024-002',
            'prenomOccupant' => 'Jane',
            'nomOccupant' => 'Smith',
            'statut' => SignalementStatus::CLOSED,
        ];

        $result = $this->factory->createSignalementInstanceFromSignalementData($data);

        $this->assertSame('fermé', $result->getStatut());
    }

    public function testCreateSignalementInstanceWithNeedValidationStatut(): void
    {
        $data = [
            'signalementUuid' => 'test123',
            'reference' => '2024-003',
            'prenomOccupant' => 'Pierre',
            'nomOccupant' => 'Martin',
            'statut' => SignalementStatus::NEED_VALIDATION,
        ];

        $result = $this->factory->createSignalementInstanceFromSignalementData($data);

        $this->assertSame('nouveau', $result->getStatut());
    }

    public function testCreateSignalementInstanceGeneratesCorrectUrl(): void
    {
        $uuid = 'unique-uuid-123';
        $data = [
            'signalementUuid' => $uuid,
            'reference' => '2024-004',
            'prenomOccupant' => 'Alice',
            'nomOccupant' => 'Dupont',
            'statut' => SignalementStatus::ACTIVE,
        ];

        $result = $this->factory->createSignalementInstanceFromSignalementData($data);

        $expectedUrl = $this->urlGenerator->generate(
            'back_signalement_view',
            ['uuid' => $uuid],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->assertSame($expectedUrl, $result->getUrl());
    }

    public function testCreateSignalementInstanceConcatenatesUsagerName(): void
    {
        $data = [
            'signalementUuid' => 'test-uuid',
            'reference' => '2024-005',
            'prenomOccupant' => 'Marie-Claire',
            'nomOccupant' => 'De La Fontaine',
            'statut' => SignalementStatus::ACTIVE,
        ];

        $result = $this->factory->createSignalementInstanceFromSignalementData($data);

        $this->assertSame('Marie-Claire De La Fontaine', $result->getUsager());
    }
}
