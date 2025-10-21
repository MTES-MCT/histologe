<?php

namespace App\Tests\Functional\Command\Cron;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SynchronizeInterventionSISHCommandTest extends KernelTestCase
{
    /**
     * @dataProvider provideNbMailSent
     */
    public function testSendNoMail(ProfileDeclarant $profileDeclarant, int $nbMail): void
    {
        $kernel = self::bootKernel();

        /** @var EntityManagerInterface $em */
        $em = $kernel->getContainer()->get('doctrine')->getManager();

        $signalements = $em->getRepository(Signalement::class)->findBy([
            'statut' => SignalementStatus::ACTIVE,
            'profileDeclarant' => ProfileDeclarant::LOCATAIRE,
        ]);

        if (ProfileDeclarant::TIERS_PRO === $profileDeclarant) {
            // Force signalements used by the esabora mocks to have a TIERS_PRO profileDeclarant value
            foreach ($signalements as $signalement) {
                /* @var Signalement $signalement */
                $signalement->setProfileDeclarant($profileDeclarant);
            }
            $em->flush();
        }

        $application = new Application($kernel);

        $command = $application->find('app:sync-esabora-sish-intervention');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $commandTester->assertCommandIsSuccessful();
        $this->assertEmailCount($nbMail);
    }

    public function provideNbMailSent(): \Generator
    {
        yield 'Signalement submitted by tiers_pro' => [ProfileDeclarant::TIERS_PRO, 1];
        yield 'Signalement submitted by locataire' => [ProfileDeclarant::LOCATAIRE, 5];
    }
}
