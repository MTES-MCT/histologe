<?php

namespace App\Tests\Functional\Command;

use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class AddPartnerCommandTest extends KernelTestCase
{
    public function testDisplayMessageSuccessfullyWhenPartnerIsNotCommune(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:add-partner');

        $commandTester = new CommandTester($command);
        $faker = Factory::create();
        $namePartner = $faker->company();
        $email = $faker->companyEmail();

        $commandTester->execute([
            'territory' => '01',
            'name' => $namePartner,
            'email' => $email,
            'is_commune' => false,
        ]);

        $commandTester->assertCommandIsSuccessful('Name Partner: '.$namePartner.' Email : '.$email);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString($namePartner, $output);
        $this->assertStringContainsString('Ain', $output);
    }

    public function testDisplayMessageSuccessfullyWhenPartnerIsCommune(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:add-partner');

        $commandTester = new CommandTester($command);
        $faker = Factory::create();
        $namePartner = $faker->company();

        $commandTester->execute([
            'territory' => '66',
            'name' => $namePartner,
            'email' => $faker->companyEmail(),
            'is_commune' => true,
            'insee' => '66136',
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString($namePartner, $output);
        $this->assertStringContainsString('Pyrénées-Orientales', $output);
    }
}
