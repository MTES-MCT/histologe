<?php

namespace App\Command\Cron;

use App\Scheduler\Message\SyncEsaboraSCHSMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:sync-esabora-schs',
    description: '[SCHS] Commande qui permet de mettre à jour l\'état d\'une affectation depuis Esabora',
)]
class SynchronizeEsaboraSCHSCommand extends AbstractCronCommand
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function configure(): void
    {
        $this->addArgument(
            'uuid_signalement',
            InputArgument::OPTIONAL,
            'Which signalement do you want to sync'
        );
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $uuidSignalement = $input->getArgument('uuid_signalement');
        $this->messageBus->dispatch(new SyncEsaboraSCHSMessage($uuidSignalement));

        $output->writeln('SCHS synchronization message dispatched.');

        return Command::SUCCESS;
    }
}
