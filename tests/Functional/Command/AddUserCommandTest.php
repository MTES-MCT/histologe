<?php

namespace App\Tests\Functional\Command;

use App\Command\AddUserCommand;
use App\Entity\Partner;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class AddUserCommandTest extends KernelTestCase
{
    public function testDisplayMessageSuccessfully(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->find('app:add-user');

        $commandTester = new CommandTester($command);
        $roles = AddUserCommand::ROLES;

        $faker = Factory::create();
        $email = $faker->email();
        $firstname = $faker->firstName();
        $lastname = $faker->lastName();

        $commandTester->execute([
            'role' => $roles[array_rand(AddUserCommand::ROLES)],
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'partner' => Partner::DEFAULT_PARTNER,
            'territory' => 13,
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(ucfirst($firstname), $output);
        $this->assertStringContainsString(mb_strtoupper($lastname), $output);
    }
}
