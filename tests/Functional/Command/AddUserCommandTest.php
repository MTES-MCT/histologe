<?php

namespace App\Tests\Functional\Command;

use App\Entity\Partner;
use App\Entity\User;
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

        $faker = Factory::create();
        $email = $faker->email();
        $firstname = $faker->firstName();
        $lastname = $faker->lastName();

        $commandTester->execute([
            'role' => array_rand(User::ROLES),
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
