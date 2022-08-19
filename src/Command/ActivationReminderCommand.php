<?php

namespace App\Command;

use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

#[AsCommand(
    name: 'app:activation-reminder',
    description: 'Add a short description for your command',
)]
class ActivationReminderCommand extends Command
{
    private EntityManagerInterface $em;
    private NotificationService $notificationService;
    private LoginLinkHandlerInterface $loginLinkHandler;

    public function __construct(EntityManagerInterface $entityManager, NotificationService $notificationService, LoginLinkHandlerInterface $loginLinkHandler)
    {
        // best practices recommend to call the parent constructor first and
        // then set your own properties. That wouldn't work in this case
        // because configure() needs the properties set in this constructor
        $this->em = $entityManager;
        $this->notificationService = $notificationService;
        $this->loginLinkHandler = $loginLinkHandler;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $i = 0;
        $em = $this->em;
        $io = new SymfonyStyle($input, $output);
        $users = $em->getRepository(User::class)->findAllInactive();
        foreach ($users as $user) {
            $loginLinkDetails = $this->loginLinkHandler->createLoginLink($user);
            $this->notificationService->send(NotificationService::TYPE_ACCOUNT_ACTIVATION, $user->getEmail(), [
                'link' => $loginLinkDetails->getUrl(),
//                'reminder' => true
            ]);
            ++$i;
        }
        $em->flush();
        $io->success($i.' user(s) notifié(s) pour activation');

        return Command::SUCCESS;
    }
}
