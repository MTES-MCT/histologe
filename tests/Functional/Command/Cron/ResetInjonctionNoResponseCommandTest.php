<?php

namespace App\Tests\Functional\Command\Cron;

use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ResetInjonctionNoResponseCommandTest extends KernelTestCase
{
    public function testDisplayMessageSuccessfullyWithOneSignalementAutoAffected(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:reset-injonction-no-response');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('2 signalements', $output);
        /** @var NotificationEmail $email1 */
        $email1 = $this->getMailerMessage();
        /** @var NotificationEmail $email2 */
        $email2 = $this->getMailerMessage(1);
        /** @var NotificationEmail $email3 */
        $email3 = $this->getMailerMessage(2);
        /** @var NotificationEmail $email4 */
        $email4 = $this->getMailerMessage(3);

        $this->assertSame('Usager Nouveau Suivi Signalement', $email1->getHeaders()->get('X-Tag')->getBody());
        $this->assertSame('Usager Nouveau Suivi Signalement', $email2->getHeaders()->get('X-Tag')->getBody());
        $this->assertSame('Usager Validation Signalement', $email3->getHeaders()->get('X-Tag')->getBody());
        $this->assertSame('Pro Nouvelle affectation', $email4->getHeaders()->get('X-Tag')->getBody());

        $this->assertEmailCount(4);
    }
}
