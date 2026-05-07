<?php

namespace App\Command\Cron;

use App\Scheduler\Message\SyncEsaboraSISHInterventionMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:sync-esabora-sish-intervention',
    description: '[SISH] Commande qui permet de mettre à jour les interventions depuis Esabora',
)]
class SynchronizeInterventionSISHCommand extends AbstractCronCommand
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
        $this->messageBus->dispatch(new SyncEsaboraSISHInterventionMessage($uuidSignalement));

        $output->writeln('SISH Intervention synchronization message dispatched.');

        return Command::SUCCESS;
    }
}
