<?php

namespace App\Tests\Unit\Dto;

use App\Dto\AddressesHistorySignalementView;
use PHPUnit\Framework\TestCase;

class AddressesHistorySignalementViewTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $signalement = new AddressesHistorySignalementView(
            url: 'https://example.com/signalement/abc123',
            ref: '2024-001',
            usager: 'John Doe',
            statut: 'Nouveau'
        );

        $this->assertSame('https://example.com/signalement/abc123', $signalement->getUrl());
        $this->assertSame('2024-001', $signalement->getRef());
        $this->assertSame('John Doe', $signalement->getUsager());
        $this->assertSame('Nouveau', $signalement->getStatut());
    }

    public function testConstructorWithNullValues(): void
    {
        $signalement = new AddressesHistorySignalementView();

        $this->assertNull($signalement->getUrl());
        $this->assertNull($signalement->getRef());
        $this->assertNull($signalement->getUsager());
        $this->assertNull($signalement->getStatut());
    }

    public function testConstructorWithPartialData(): void
    {
        $signalement = new AddressesHistorySignalementView(
            ref: '2024-001',
            statut: 'En cours'
        );

        $this->assertNull($signalement->getUrl());
        $this->assertSame('2024-001', $signalement->getRef());
        $this->assertNull($signalement->getUsager());
        $this->assertSame('En cours', $signalement->getStatut());
    }

    public function testImmutability(): void
    {
        $signalement = new AddressesHistorySignalementView(
            url: 'https://example.com/signalement/abc123',
            ref: '2024-001',
            usager: 'John Doe',
            statut: 'Nouveau'
        );

        // Verify that all properties remain the same
        $this->assertSame('https://example.com/signalement/abc123', $signalement->getUrl());
        $this->assertSame('2024-001', $signalement->getRef());
        $this->assertSame('John Doe', $signalement->getUsager());
        $this->assertSame('Nouveau', $signalement->getStatut());
    }
}
