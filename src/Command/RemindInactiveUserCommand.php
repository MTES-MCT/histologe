<?php

namespace App\Command;

use App\Entity\User;
use App\Manager\UserManager;
use App\Service\Mailer\Notification;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Token\TokenGenerator;
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
        private TokenGenerator $tokenGenerator,
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

            $link = $this->generateLink($user);
            $this->notificationMailerRegistry->send(
                new Notification(
                    NotificationMailerType::TYPE_ACCOUNT_ACTIVATION,
                    $user->getEmail(),
                    [
                        'link' => $link,
                        'nb_signalements' => $userItem['nb_signalements'],
                        'reminder' => true,
                    ],
                    $user->getTerritory()
                )
            );
        }

        $nbUsers = \count($userList);
        $io->success(sprintf('%s users has been notified', $nbUsers));

        $this->notificationMailerRegistry->send(
            new Notification(
                NotificationMailerType::TYPE_CRON,
                $this->parameterBag->get('admin_email'),
                [
                    'cron_label' => 'demande d\'activation de compte',
                    'count' => $nbUsers,
                    'message' => $nbUsers > 1 ? 'utilisateurs ont été notifiées' : 'utilisateur a été notifiée',
                ],
                null
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
