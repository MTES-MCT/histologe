<?php

namespace App\Command;

use App\Manager\TerritoryManager;
use App\Manager\UserManager;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(
    name: 'app:activation-reminder',
    description: 'Add a short description for your command',
)]
class ActivationReminderCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService,
        private UserManager $userManager,
        private TerritoryManager $territoryManager,
        private RouterInterface $router,
        private string $hostUrl
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('zip', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $i = 0;
        $io = new SymfonyStyle($input, $output);
        $territory = $this->territoryManager->findOneBy(['zip' => $input->getArgument('zip')]);
        $users = $this->userManager->getRepository()->findALlInactive($territory);
        foreach ($users as $user) {
            $this->notificationService->send(
                NotificationService::TYPE_ACCOUNT_ACTIVATION,
                $user->getEmail(),
                ['link' => $this->hostUrl.$this->router->generate('login_activation')],
                $territory);
            ++$i;
        }
        $io->success($i.' user(s) notifié(s) pour activation');

        return Command::SUCCESS;
    }
}
