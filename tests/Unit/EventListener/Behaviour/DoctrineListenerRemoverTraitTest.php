<?php

namespace App\Tests\Unit\EventListener\Behaviour;

use App\EventListener\Behaviour\DoctrineListenerRemoverTrait;
use Doctrine\Common\EventManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DoctrineListenerRemoverTraitTest extends TestCase
{
    private MockObject&EventManager $eventManagerMock;

    protected function setUp(): void
    {
        $this->eventManagerMock = $this->createMock(EventManager::class);
    }

    public function testRemoveListenerWhenClassUsingTrait(): void
    {
        $listener = $this->createMock(ListenerToRemove::class);

        $this->eventManagerMock
            ->expects($this->once())
            ->method('getListeners')
            ->with('postPersist')
            ->willReturn([$listener]);

        $this->eventManagerMock
            ->expects($this->once())
            ->method('removeEventListener')
            ->with(['postPersist'], $listener);

        $classUsingTrait = new class {
            use DoctrineListenerRemoverTrait;
        };

        $classUsingTrait->removeListener(
            $this->eventManagerMock,
            ListenerToRemove::class,
            'postPersist'
        );
    }

    public function testDoNotRemoveListenerWhenClassNotUsingTrait(): void
    {
        $listener = $this->createMock(Listener::class);

        $this->eventManagerMock
            ->expects($this->once())
            ->method('getListeners')
            ->with('postPersist')
            ->willReturn([$listener]);

        $this->eventManagerMock
            ->expects($this->never())
            ->method('removeEventListener');

        $classUsingTrait = new class {
            use DoctrineListenerRemoverTrait;
        };

        $classUsingTrait->removeListener(
            $this->eventManagerMock,
            ListenerToRemove::class,
            'postPersist'
        );
    }
}

class ListenerToRemove // As EntityHistoryListener
{
}

class Listener
{
}
