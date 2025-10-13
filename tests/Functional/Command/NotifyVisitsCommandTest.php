<?php

namespace App\Tests\Functional\Command;

use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

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

        $this->assertEmailCount(10);
        $this->assertEmailSubjectContains($this->getMailerMessages()[1], '2024-02');
        $this->assertEmailSubjectContains($this->getMailerMessages()[4], '2022-6');
        $this->assertEmailSubjectContains($this->getMailerMessages()[6], '2023-26');
        /** @var NotificationEmail $message */
        foreach ($this->getMailerMessages() as $message) {
            $subject = $message->getSubject();
            $body = $message->getHtmlBody();

            $this->assertStringNotContainsString('2022-3', $subject, 'Aucun mail ne doit être envoyé pour le signalement fermé 2022-3');
            $this->assertStringNotContainsString('2022-3', $body, 'Aucun mail ne doit être envoyé pour le signalement fermé 2022-3');
        }
    }
}
