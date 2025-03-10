<?php

namespace App\Tests\Functional\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class NotifyVisitsCommandTest extends KernelTestCase
{
    public function testDisplayMessageSuccessfully(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:notify-visits');

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $this->assertEmailCount(9);
        $this->assertEmailSubjectContains($this->getMailerMessages()[1], '2024-02');
        $this->assertEmailSubjectContains($this->getMailerMessages()[4], '2022-6');
        $this->assertEmailSubjectContains($this->getMailerMessages()[6], '2023-26');
    }
}
