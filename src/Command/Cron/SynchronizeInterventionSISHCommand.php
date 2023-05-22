<?php

namespace App\Command\Cron;

use App\Entity\Enum\PartnerType;
use App\Manager\JobEventManager;
use App\Repository\AffectationRepository;
use App\Service\Esabora\EsaboraManager;
use App\Service\Esabora\Handler\InterventionSISHHandlerInterface;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:sync-intervention-esabora-sish',
    description: '[SISH] Commande qui permet de mettre à jour les interventions depuis Esabora',
)]
class SynchronizeInterventionSISHCommand extends AbstractSynchronizeEsaboraCommand
{
    private iterable $interventionHandlers;

    public function __construct(
        private readonly EsaboraManager $esaboraManager,
        private readonly JobEventManager $jobEventManager,
        private readonly AffectationRepository $affectationRepository,
        private readonly SerializerInterface $serializer,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
        #[TaggedIterator(
            'app.intervention_sish_handler',
            defaultPriorityMethod: 'getPriority'
        )] iterable $interventionHandlers
    ) {
        parent::__construct(
            $this->esaboraManager,
            $this->jobEventManager,
            $this->affectationRepository,
            $this->serializer,
            $this->notificationMailerRegistry,
            $this->parameterBag,
        );
        $this->interventionHandlers = $interventionHandlers;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $affectations = $this->affectationRepository->findAffectationSubscribedToEsabora(PartnerType::ARS);

        foreach ($affectations as $affectation) {
            /** @var InterventionSISHHandlerInterface $interventionHandler */
            foreach ($this->interventionHandlers as $key => $interventionHandler) {
                $interventionHandler->handle($affectation);
                $io->writeln(sprintf('#%s: %s was executed', $key, $interventionHandler->getServiceName()));
            }
        }

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: $this->parameterBag->get('admin_email'),
                cronLabel: '[ARS] Synchronisation des interventions depuis Esabora',
            )
        );

        return Command::SUCCESS;
    }
}
