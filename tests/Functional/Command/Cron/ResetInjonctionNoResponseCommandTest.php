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
        $this->assertEmailCount(4);
        $expectedTags = [
            'Usager Nouveau Suivi Signalement',
            'Usager Nouveau Suivi Signalement',
            'Usager Validation Signalement',
            'Pro Nouvelle affectation',
        ];

        for ($i = 0; $i < 6; ++$i) {
            /** @var NotificationEmail $email */
            $email = $this->getMailerMessage($i);
            $xTag = $email->getHeaders()->get('X-Tag')->getBody();
            $this->assertNotEmpty($xTag, "La valeur de l'en-tête X-Tag est vide dans l'email $i");
            $actualTags[] = $xTag;
        }

        $this->assertEmpty(
            array_diff($expectedTags, $actualTags),
            sprintf(
                'Les X-Tag ne correspondent pas. Attendu : %s. Reçu : %s.',
                implode(', ', $expectedTags),
                implode(', ', $actualTags)
            )
        );
    }
}
