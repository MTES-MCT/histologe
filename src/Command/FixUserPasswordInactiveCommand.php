<?php

namespace App\Command;

use App\Entity\User;
use App\Manager\UserManager;
use App\Service\Token\TokenGeneratorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-user-password-inactive',
    description: 'Generate random password user cause user should have a password to be validate',
)]
class FixUserPasswordInactiveCommand extends Command
{
    public function __construct(
        private UserManager $userManager,
        private TokenGeneratorInterface $tokenGenerator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->userManager->findBy(['password' => null]);

        /** @var User $user */
        foreach ($users as $user) {
            $user->setPassword($this->tokenGenerator->generateToken());
            $this->userManager->persist($user);
        }
        $this->userManager->flush();
        $io->success(sprintf('%s user has been set a random password useful for validation', \count($users)));

        return Command::SUCCESS;
    }
}
