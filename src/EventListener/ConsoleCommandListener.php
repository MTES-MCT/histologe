<?php

namespace App\EventListener;

use App\Dto\Command\CommandContext;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'console.command', method: 'onConsoleCommand')]
readonly class ConsoleCommandListener
{
    public function __construct(private CommandContext $commandContext)
    {
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $this->commandContext->setCommandName($event->getCommand()->getName());
    }
}
