<?php

namespace App\Command\Cron;

use App\Repository\SignalementRepository;
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
    name: 'app:ask-travaux-mise-en-conformite',
    description: 'Sends ask travaux mise en conformité emails to users.',
)]
class SendAskTravauxMiseEnConformiteCommand extends AbstractCronCommand
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        // histologe is the name of the production scalingo app
        // test is injected in SendAskTravauxMiseEnConformiteCommandTest
        // dev is for local development
        if ('histologe' !== getenv('APP') && 'test' !== getenv('APP') && 'dev' !== $_ENV['APP_ENV']) {
            $this->io->error('This command is only available on production environment, test environment and dev environment');

            return Command::FAILURE;
        }

        $signalements = $this->signalementRepository->findSignalementsToAskTravauxMiseEnConformite();
        $nbMails = 0;
        foreach ($signalements as $signalement) {
            $toRecipients = $signalement->getMailUsagers();
            foreach ($toRecipients as $toRecipient) {
                $this->notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_ASK_TRAVAUX_MISE_EN_CONFORMITE,
                        to: $toRecipient,
                        signalement: $signalement,
                    )
                );
                ++$nbMails;
            }
        }
        $message = $nbMails.' emails de demande d\'avancement des travaux envoyés pour '.count($signalements).' signalements.';
        $this->io->success($message);
        if ($nbMails) {
            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_CRON,
                    to: (string) $this->parameterBag->get('admin_email'),
                    message: $message,
                    cronLabel: 'Emails de demande d\'avancement des travaux'
                )
            );
        }

        return Command::SUCCESS;
    }
}
