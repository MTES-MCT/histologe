<?php

namespace App\Command;

use App\Entity\User;
use App\Manager\UserManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:fix-user-uuid',
    description: 'Fix User uuid to execute only one',
)]
class FixUserUuidCommand extends Command
{
    public function __construct(private UserManager $userManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Do you want to continue? (y/n): ', false, '/^(y|o)/i');
        if (!$helper->ask($input, $output, $question)) {
            $io->info('No users have been updated');

            return Command::SUCCESS;
        }

        $users = $this->userManager->findAll();

        /** @var User $user */
        foreach ($users as $user) {
            $user->setUuid(Uuid::v4());
            $this->userManager->persist($user);
        }

        $this->userManager->flush();

        $io->success(sprintf('%s users have an uuid', \count($users)));

        return Command::SUCCESS;
    }
}
