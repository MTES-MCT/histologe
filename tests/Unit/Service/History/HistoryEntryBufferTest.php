<?php

namespace App\Tests\Unit\Service\History;

use App\Entity\HistoryEntry;
use App\Entity\User;
use App\Service\History\HistoryEntryBuffer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class HistoryEntryBufferTest extends KernelTestCase
{
    public function testFlushEmpty(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager->expects($this->never())->method('clear');
        $historyEntryBuffer = new HistoryEntryBuffer($entityManager);

        $historyEntryBuffer->flushPendingHistoryEntries();
    }

    public function testFlushNotEmpty(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager->expects($this->once())->method('clear');
        $entityManager->expects($this->exactly(2))->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $historyEntryBuffer = new HistoryEntryBuffer($entityManager);
        $historyEntry1 = new HistoryEntry();
        $historyEntry1->setEntity(new User());
        $historyEntry2 = new HistoryEntry();
        $historyEntry2->setEntity(new User());

        $historyEntryBuffer->add('un', $historyEntry1);
        $historyEntryBuffer->add('deux', $historyEntry2);

        $historyEntryBuffer->flushPendingHistoryEntries();
    }

    public function testExistAndUpdate(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager->expects($this->once())->method('clear');
        $entityManager->expects($this->exactly(1))->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $historyEntryBuffer = new HistoryEntryBuffer($entityManager);
        $historyEntry1 = new HistoryEntry();
        $historyEntry1->setChanges(['key1' => 'value1']);
        $historyEntry1->setEntity(new User());

        $historyEntryBuffer->add('un', $historyEntry1);
        $this->assertTrue($historyEntryBuffer->exist('un'));

        $historyEntryBuffer->update('un', ['key2' => 'value2']);
        $this->assertTrue($historyEntryBuffer->exist('un'));

        $historyEntryBuffer->flushPendingHistoryEntries();
        $this->assertFalse($historyEntryBuffer->exist('un'));
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $historyEntry1->getChanges());
    }
}
