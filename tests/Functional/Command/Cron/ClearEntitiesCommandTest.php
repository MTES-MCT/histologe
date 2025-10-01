<?php

declare(strict_types=1);

namespace App\Tests\Functional\Command\Cron;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\Connection as MessengerConnection;

class ClearEntitiesCommandTest extends KernelTestCase
{
    /**
     * @throws Exception
     */
    public function testDisplayMessageSuccessfully(): void
    {
        $kernel = self::bootKernel();
        // Le bundle DAMA\DoctrineTestBundle est présent, il remplace donc les connexions Doctrine
        // par des connexions statiques partagées entre tous les tests, pour optimiser les performances.
        // Or, lorsqu'on exécute des commandes console dans un test fonctionnel, cette gestion statique
        // des connexions peut provoquer des erreurs ("There is no active transaction") car la commande
        // ne s'exécute pas dans le même cycle de transaction que PHPUnit.
        // On désactive donc ici le mode "static connections" uniquement pour ce test, afin que la commande
        // utilise sa propre connexion Doctrine propre et évite ces erreurs.
        if (class_exists(StaticDriver::class)) {
            StaticDriver::setKeepStaticConnections(false);
        }

        /** @var MessengerConnection $messengerConnection
         */
        $messengerConnection = self::getContainer()->get('messenger.transport.failed'); // Initialise la transport failed
        $messengerConnection->setup();  // crée la table messenger_messages si elle n’existe pas

        // Reconnecte Doctrine
        /** @var Connection $connection */
        $connection = self::getContainer()->get(Connection::class);
        $connection->close();
        $connection->connect();

        $application = new Application($kernel);

        $command = $application->find('app:clear-entities');

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('JobEvent(s) deleted', $output);
        $this->assertStringContainsString('Notification(s) deleted', $output);
        $this->assertStringContainsString('SignalementDraft(s) deleted', $output);
        $this->assertStringContainsString('ApiUserToken(s) deleted', $output);
        $this->assertStringContainsString('messenger_messages(s) deleted', $output);
        $this->assertEmailCount(5);
    }
}
