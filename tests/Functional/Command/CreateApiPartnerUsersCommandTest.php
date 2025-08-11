<?php

namespace App\Tests\Functional\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CreateApiPartnerUsersCommandTest extends KernelTestCase
{
    public function testNewApiUserToExistingPartner(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:create-api-partner-users');
        // --zip=44 --partner_name=Jambon --bo_email=soupe@soupe.fr"
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--api_email' => 'api-nouveau@signal-logement.fr',
            '--partner_id' => 1,
        ]);

        $commandTester->assertCommandIsSuccessful();
    }

    public function testExistingApiUserToExistingPartner(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:create-api-partner-users');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--api_email' => 'api-01@signal-logement.fr',
            '--partner_id' => 1,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('User already exists with API e-mail', $output, $output);
    }

    public function testNewApiUserToNewPartnerWithNewBoUser(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:create-api-partner-users');

        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--api_email' => 'api-nouveau@signal-logement.fr',
            '--zip' => 44,
            '--partner_name' => 'Nouveau partenaire',
            '--bo_email' => 'bo-nouveau@signal-logement.fr',
        ]);

        $commandTester->assertCommandIsSuccessful();
    }

    public function testNewApiUserToNewPartnerWithExistingBoUser(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:create-api-partner-users');

        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--api_email' => 'api-nouveau@signal-logement.fr',
            '--zip' => 44,
            '--partner_name' => 'Nouveau partenaire',
            '--bo_email' => 'user-13-01@signal-logement.fr',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('User already exists with BO e-mail', $output, $output);
    }

    public function testTooManyOptions(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:create-api-partner-users');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--api_email' => 'api-too-many-options@signal-logement.fr',
            '--partner_id' => 1,
            '--zip' => 13,
            '--partner_name' => 'Nouveau partenaire',
            '--bo_email' => 'user-13-02-signal-logement.fr',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Too many options', $output, $output);
    }
}
