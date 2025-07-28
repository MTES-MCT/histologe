<?php

namespace App\Tests\Functional\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mime\Email;

class NotifyVisitsCommandTest extends KernelTestCase
{
    public function testDisplayMessageSuccessfully(): void
    {
        putenv('APP=test');
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:notify-visits');

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        /** @var Email[] $emails */
        $emails = $this->getMailerMessages();
        foreach ($emails as $email) {
            foreach ($email->getTo() as $emailTo) {
                echo $emailTo->getAddress().'|';
            }
            echo $email->getSubject()."\n";
        }

        $this->assertEquals(1, 1);
        $this->assertEquals(1, 1);
        $this->assertEmailCount(10);
        $this->assertEmailSubjectContains($this->getMailerMessages()[1], '2024-02');
        $this->assertEmailSubjectContains($this->getMailerMessages()[4], '2022-6');
        $this->assertEmailSubjectContains($this->getMailerMessages()[6], '2023-26');
    }
}
