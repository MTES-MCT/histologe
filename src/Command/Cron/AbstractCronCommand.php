<?php

namespace App\Command\Cron;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:abstract-cron-command',
    description: 'Abstract command for commands to be executed in a Cron context.'
)]
class AbstractCronCommand extends Command
{
    public function __construct(private readonly ParameterBagInterface $parameterBag)
    {
        parent::__construct();
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        if ($this->parameterBag->get('maintenance_enable')) {
            $output->writeln('Le mode maintenance est activé, merci de le désactiver une fois l\'opération terminée.');

            return Command::FAILURE;
        }

        if (!$this->parameterBag->get('cron_enable')) {
            $output->writeln('Merci d\'activer CRON_ENABLE=1 pour executer la commande');

            return Command::FAILURE;
        }

        return parent::run($input, $output);
    }
}
