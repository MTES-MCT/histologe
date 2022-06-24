<?php

namespace App\Command;

use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clear-notification',
    description: 'Add a short description for your command',
)]
class ClearNotificationCommand extends Command
{

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        // best practices recommend to call the parent constructor first and
        // then set your own properties. That wouldn't work in this case
        // because configure() needs the properties set in this constructor
        $this->em = $entityManager;

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
        $io = new SymfonyStyle($input, $output);

        $notifications = $this->em->getRepository(Notification::class)->findOlderThan(30);
        foreach ($notifications as $notification) {
            $this->em->remove($notification);
        }
        $this->em->flush();

        $io->success(count($notifications) . ' notification(s) deleted !');

        return Command::SUCCESS;
    }
}
