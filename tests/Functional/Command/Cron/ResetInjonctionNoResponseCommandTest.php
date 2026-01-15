<?php

namespace App\Tests\Functional\Command\Cron;

use App\Repository\SignalementRepository;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ResetInjonctionNoResponseCommandTest extends KernelTestCase
{
    public function testDisplayMessageSuccessfullyWithoutAutoAffectation(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:reset-injonction-no-response');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('1 signalements', $output);
        $nbMails = 2;
        $this->assertEmailCount($nbMails);
        $expectedTags = [
            'Usager Nouveau Suivi Signalement',
        ];

        $actualTags = [];
        for ($i = 0; $i < $nbMails; ++$i) {
            /** @var NotificationEmail $email */
            $email = $this->getMailerMessage($i);
            $xTag = $email->getHeaders()->get('X-Tag');
            if ($xTag) {
                $this->assertNotEmpty($xTag->getBody(), "La valeur de l'en-tête X-Tag est vide dans l'email $i");
                $actualTags[] = $xTag->getBody();
            }
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

    public function testDisplayMessageSuccessfullyWithOneSignalementAutoAffected(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        // Remove suivi with category INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE to trigger auto-affectation
        $referenceInjonction = '2364';
        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy(['referenceInjonction' => $referenceInjonction]);
        $suivis = $signalement->getSuivis()->filter(function ($suivi) {
            return 'INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE' === $suivi->getCategory()->value;
        });
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        foreach ($suivis as $suivi) {
            $entityManager->remove($suivi);
        }
        $entityManager->flush();

        $command = $application->find('app:reset-injonction-no-response');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('2 signalements', $output);
        $nbMails = 6;
        $this->assertEmailCount($nbMails);
        $expectedTags = [
            'Usager Nouveau Suivi Signalement',
            'Usager Nouveau Suivi Signalement',
            'Usager Validation Signalement',
            'Pro Nouvelle affectation',
        ];

        $actualTags = [];
        for ($i = 0; $i < $nbMails; ++$i) {
            /** @var NotificationEmail $email */
            $email = $this->getMailerMessage($i);
            $xTag = $email->getHeaders()->get('X-Tag');
            if ($xTag) {
                $this->assertNotEmpty($xTag->getBody(), "La valeur de l'en-tête X-Tag est vide dans l'email $i");
                $actualTags[] = $xTag->getBody();
            }
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
