<?php

namespace App\Command;

use App\Entity\User;
use App\Manager\UserManager;
use App\Service\NotificationService;
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
        private NotificationService $notificationService,
        private UrlGeneratorInterface $urlGenerator,
        private TokenGenerator $tokenGenerator,
        private ParameterBagInterface $parameterBag,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('--debug', null, InputOption::VALUE_NONE, 'Check how many maisl to send')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userList = $this->userManager->getRepository()->findInactiveWithNbAffectationPending();
        if ($input->getOption('debug')) {
            $io->info(sprintf('%s users will be notified', \count($userList)));

            return Command::SUCCESS;
        }

        /* @var User $user */
        foreach ($userList as $userItem) {
            $user = $this->userManager->loadUserToken($userItem['email']);
            $this->userManager->save($user);

            $link = $this->generateLink($user);
            $this->notificationService->send(
                NotificationService::TYPE_ACCOUNT_ACTIVATION,
                $user->getEmail(),
                [
                    'link' => $link,
                    'nb_signalements' => $userItem['nb_signalements'],
                    'reminder' => true,
                ],
                $user->getTerritory()
            );
        }

        $io->success(sprintf('%s users will be notified', \count($userList)));

        return Command::SUCCESS;
    }

    private function generateLink(User $user): string
    {
        return
            $this->parameterBag->get('host_url').
            $this->urlGenerator->generate('activate_account', ['token' => $user->getToken()]);
    }
}
