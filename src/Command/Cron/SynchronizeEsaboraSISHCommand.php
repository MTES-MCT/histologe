<?php

namespace App\Command\Cron;

use App\Entity\Enum\PartnerType;
use App\Repository\AffectationRepository;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
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
        private readonly EsaboraManager $esaboraManager,
        private readonly AffectationRepository $affectationRepository,
        private readonly SerializerInterface $serializer,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct(
            $this->esaboraManager,
            $this->affectationRepository,
            $this->serializer,
            $this->notificationMailerRegistry,
            $this->parameterBag,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->synchronizeStatus(
            $input,
            $output,
            $this->esaboraService,
            PartnerType::ARS,
            'Reference_Dossier'
        );

        return Command::SUCCESS;
    }
}
