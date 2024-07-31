<?php

namespace App\Tests\Functional\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class AddAutoAffectationRuleCommandTest extends KernelTestCase
{
    public function testDisplayMessageSuccessfully(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:add-auto-affectation-rule');

        $commandTester = new CommandTester($command);

        $territory = 44;
        $partnerType = 'ASSOCIATION';
        $status = 'ACTIVE';
        $profileDeclarant = 'occupant';
        $inseeToInclude = 'partner_list';
        $inseeToExclude = '44850,44600';
        $partnerToExclude = '';
        $parc = 'public';
        $allocataire = 'caf';
        $commandTester->execute([
            'territory' => $territory,
            'partnerType' => $partnerType,
            'status' => $status,
            'profileDeclarant' => $profileDeclarant,
            'inseeToInclude' => $inseeToInclude,
            'inseeToExclude' => $inseeToExclude,
            'partnerToExclude' => $partnerToExclude,
            'parc' => $parc,
            'allocataire' => $allocataire,
        ]);

        $output = $commandTester->getDisplay();
        $commandTester->assertCommandIsSuccessful();

        $this->assertStringContainsString(ucfirst($partnerType), $output);
    }

    public function testDisplayMessageError(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:add-auto-affectation-rule');

        $commandTester = new CommandTester($command);

        $territory = 440;
        $partnerType = 'EPCI';
        $status = 'ACTIVE';
        $profileDeclarant = 'occupant';
        $inseeToInclude = 'partner_list';
        $inseeToExclude = '44850,44600';
        $partnerToExclude = '';
        $parc = 'public';
        $allocataire = 'caf';
        $commandTester->execute([
            'territory' => $territory,
            'partnerType' => $partnerType,
            'status' => $status,
            'profileDeclarant' => $profileDeclarant,
            'inseeToInclude' => $inseeToInclude,
            'inseeToExclude' => $inseeToExclude,
            'partnerToExclude' => $partnerToExclude,
            'parc' => $parc,
            'allocataire' => $allocataire,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Territory does not exists', $output);
    }
}
