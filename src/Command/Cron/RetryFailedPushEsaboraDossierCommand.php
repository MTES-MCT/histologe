<?php

namespace App\Command\Cron;

use App\Entity\Enum\InterfacageType;
use App\Messenger\InterconnectionBus;
use App\Repository\JobEventRepository;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:retry-failed-push-esabora-dossier',
    description: 'Retry failed push to esabora dossier si-sh'
)]
class RetryFailedPushEsaboraDossierCommand extends AbstractCronCommand
{
    public function __construct(
        private readonly JobEventRepository $jobEventRepository,
        private readonly InterconnectionBus $esaboraBus,
        private readonly ParameterBagInterface $parameterBag,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $affectations = $this->jobEventRepository->findFailedJobEvents(
            InterfacageType::ESABORA->name,
            AbstractEsaboraService::ACTION_PUSH_DOSSIER_ADRESSE
        );

        foreach ($affectations as $affectation) {
            $this->esaboraBus->dispatch($affectation);
            $io->success(\sprintf(
                '[%s] Dossier %s pushed to esabora',
                $affectation->getPartner()->getType()->value,
                $affectation->getSignalement()->getUuid()
            ));
        }

        $nbAffectations = \count($affectations);
        $io->success(\sprintf('%s dossier(s) has been pushed', $nbAffectations));

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: $this->parameterBag->get('admin_email'),
                message: \sprintf('%s dossier(s) ont été repoussé vers SI-SH', $nbAffectations),
                cronLabel: '[ARS] Reprise de dossiers SI-SH en erreur'),
        );

        return Command::SUCCESS;
    }
}
