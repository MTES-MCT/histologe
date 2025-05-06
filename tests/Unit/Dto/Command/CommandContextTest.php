<?php

namespace App\Tests\Unit\Dto\Command;

use App\Dto\Command\CommandContext;
use PHPUnit\Framework\TestCase;

class CommandContextTest extends TestCase
{
    public function testCommandContextDto(): void
    {
        $commandContext = (new CommandContext())
            ->setCommandName('app:add-auto-affectation-rule');

        $this->assertEquals('app:add-auto-affectation-rule', $commandContext->getCommandName());
    }
}
