<?php

namespace App\Command\Cron;

use App\Manager\UserManager;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:remind-inactive-user',
    description: 'Remind inactive users with nb pending affectations',
)]
class RemindInactiveUserCommand extends AbstractCronCommand
{
    public function __construct(
        private readonly UserManager $userManager,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function configure(): void
    {
        $this->addOption(
            '--debug',
            null,
            InputOption::VALUE_NONE,
            'Check how many emails will be send'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // histologe is the name of the production scalingo app
        // test is injected in RemindInactiveUserCommandTest
        // dev is for local development
        if ('histologe' !== getenv('APP') && 'test' !== getenv('APP') && 'dev' !== $_ENV['APP_ENV']) {
            $io->error('This command is only available on production environment, test environment and dev environment');

            return Command::FAILURE;
        }

        /** @var UserRepository $userRepository */
        $userRepository = $this->userManager->getRepository();
        $userList = $userRepository->findInactiveWithNbAffectationPending();
        $nbUsers = \count($userList);
        if ($input->getOption('debug')) {
            $io->info(\sprintf('%s users will be notified', $nbUsers));

            return Command::SUCCESS;
        }

        foreach ($userList as $userItem) {
            $user = $this->userManager->findOneBy(['email' => $userItem['email']]);

            if ($user->isActivateAccountNotificationEnabled()) {
                $this->notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_ACCOUNT_ACTIVATION_REMINDER,
                        to: $user->getEmail(),
                        user: $user,
                        params: [
                            'nb_signalements' => $userItem['nb_signalements'],
                        ],
                    )
                );
            }
        }

        $nbUsers = \count($userList);
        $io->success(\sprintf('%s users has been notified', $nbUsers));

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: $this->parameterBag->get('admin_email'),
                message: $nbUsers > 1 ? 'utilisateurs ont été notifiées' : 'utilisateur a été notifiée',
                cronLabel: 'demande d\'activation de compte',
                cronCount: $nbUsers,
            )
        );

        return Command::SUCCESS;
    }
}
