<?php

namespace App\Command\Cron;

use App\Entity\Enum\PartnerType;
use App\Repository\AffectationRepository;
use App\Service\Esabora\Handler\InterventionSISHHandlerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:sync-intervention-esabora-sish',
    description: '[SISH] Commande qui permet de mettre à jour les interventions depuis Esabora',
)]
class SynchronizeInterventionSISHCommand extends AbstractCronCommand
{
    private iterable $interventionHandlers;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly AffectationRepository $affectationRepository,
        #[TaggedIterator(
            'app.intervention_sish_handler',
            defaultPriorityMethod: 'getPriority'
        )] iterable $interventionHandlers
    ) {
        parent::__construct($this->parameterBag);
        $this->interventionHandlers = $interventionHandlers;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $affectations = $this->affectationRepository->findAffectationSubscribedToEsabora(PartnerType::ARS);

        foreach ($affectations as $affectation) {
            /** @var InterventionSISHHandlerInterface $interventionHandler */
            foreach ($this->interventionHandlers as $interventionHandler) {
                $interventionHandler->handle($affectation);
            }
        }

        return Command::SUCCESS;
    }
}
