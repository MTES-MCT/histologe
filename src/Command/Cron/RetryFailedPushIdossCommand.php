<?php

declare(strict_types=1);

namespace App\Command\Cron;

use App\Entity\Enum\InterfacageType;
use App\Messenger\InterconnectionBus;
use App\Repository\JobEventRepository;
use App\Service\Interconnection\Idoss\IdossService;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;

#[AsCommand(
    name: 'app:retry-failed-push-idoss',
    description: 'Retry failed push to idoss'
)]
class RetryFailedPushIdossCommand extends AbstractCronCommand
{
    public function __construct(
        private readonly JobEventRepository $jobEventRepository,
        private readonly InterconnectionBus $interconnectionBus,
        private readonly ParameterBagInterface $parameterBag,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
    ) {
        parent::__construct($this->parameterBag);
    }

    /**
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $affectations = $this->jobEventRepository->findFailedJobEvents(
            InterfacageType::IDOSS->value,
            IdossService::ACTION_PUSH_DOSSIER
        );

        foreach ($affectations as $affectation) {
            $io->writeln(\sprintf('%s', $affectation->getSignalement()->getReference()));
            $this->interconnectionBus->dispatch($affectation);
            $io->success(\sprintf(
                '[%s] Dossier %s pushed to idoss',
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
                message: \sprintf('%s dossier(s) ont été repoussé vers Idoss', $nbAffectations),
                cronLabel: '[IDOSS] Reprise de dossiers Idoss en erreur'),
        );

        return Command::SUCCESS;
    }
}
