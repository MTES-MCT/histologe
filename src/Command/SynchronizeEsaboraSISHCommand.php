<?php

namespace App\Command;

use App\Entity\Enum\PartnerType;
use App\Manager\AffectationManager;
use App\Manager\JobEventManager;
use App\Service\Esabora\EsaboraSISHService;
use App\Service\Mailer\NotificationMailerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:sync-esabora-sish',
    description: '[SISH] Commande qui permet de mettre à jour l\'état d\'une affectation depuis Esabora',
)]
class SynchronizeEsaboraSISHCommand extends AbstractSynchronizeEsaboraCommand
{
    public function __construct(
        private readonly EsaboraSISHService $esaboraService,
        private readonly AffectationManager $affectationManager,
        private readonly JobEventManager $jobEventManager,
        private readonly SerializerInterface $serializer,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
        string $name = 'app:sync-esabora-sish'
    ) {
        parent::__construct(
            $this->parameterBag,
            $this->affectationManager,
            $this->jobEventManager,
            $this->serializer,
            $this->notificationMailerRegistry,
            $name
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->synchronize(
            $input,
            $output,
            $this->esaboraService,
            PartnerType::ARS,
            'Reference_Dossier'
        );

        return Command::SUCCESS;
    }
}
