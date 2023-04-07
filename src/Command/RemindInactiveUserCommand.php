<?php

namespace App\Command;

use App\Entity\User;
use App\Manager\UserManager;
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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'app:remind-inactive-user',
    description: 'Remind inactive users with nb pending affectations',
)]
class RemindInactiveUserCommand extends Command
{
    public function __construct(
        private UserManager $userManager,
        private NotificationMailerRegistry $notificationMailerRegistry,
        private UrlGeneratorInterface $urlGenerator,
        private ParameterBagInterface $parameterBag,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('--debug', null, InputOption::VALUE_NONE, 'Check how many emails will be send')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userList = $this->userManager->getRepository()->findInactiveWithNbAffectationPending();
        $nbUsers = \count($userList);
        if ($input->getOption('debug')) {
            $io->info(sprintf('%s users will be notified', $nbUsers));

            return Command::SUCCESS;
        }

        foreach ($userList as $userItem) {
            $user = $this->userManager->loadUserToken($userItem['email']);
            $this->userManager->save($user);

            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_ACCOUNT_ACTIVATION_REMINDER,
                    to: $user->getEmail(),
                    territory: $user->getTerritory(),
                    user: $user,
                    params: [
                        'nb_signalements' => $userItem['nb_signalements'],
                    ],
                )
            );
        }

        $nbUsers = \count($userList);
        $io->success(sprintf('%s users has been notified', $nbUsers));

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

    private function generateLink(User $user): string
    {
        return
            $this->parameterBag->get('host_url').
            $this->urlGenerator->generate('activate_account', ['token' => $user->getToken()]);
    }
}
