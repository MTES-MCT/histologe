<?php

namespace App\EventSubscriber;

use App\Service\History\HistoryEntryBuffer;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class HistoryEntryFlushSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly HistoryEntryBuffer $historyEntryBuffer,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::TERMINATE => 'onKernelTerminate',
            ConsoleEvents::TERMINATE => 'onConsoleTerminate',
        ];
    }

    /*public function onKernelResponse(ResponseEvent $event): void
    {
        $this->historyEntryBuffer->flushPendingHistoryEntries();
    }*/

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $this->historyEntryBuffer->flushPendingHistoryEntries();
    }

    public function onConsoleTerminate(): void
    {
        $this->historyEntryBuffer->flushPendingHistoryEntries();
    }
}
