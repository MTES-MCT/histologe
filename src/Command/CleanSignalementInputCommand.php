<?php

namespace App\Command;

use App\Entity\Signalement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clean-signalement-input',
    description: 'Add a short description for your command',
)]
class CleanSignalementInputCommand extends Command
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
        $i = 0;
        $em = $this->em;
        $io = new SymfonyStyle($input, $output);
        $signalements = $em->getRepository(Signalement::class)->findAll();
        foreach ($signalements as $signalement) {
            $signalement->setDetails(str_replace('\r\n', '<br>', $signalement->getDetails())) && $i++ && $em->persist($signalement);
        }
        $em->flush();
        $io->success($i . ' signalements nettoyés');

        return Command::SUCCESS;
    }
}
