<?php

namespace App\Command\Cron;

use App\Entity\SignalementDraft;
use App\Manager\SignalementDraftManager;
use App\Repository\SignalementDraftRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\SignalementDraftHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:remind-pending-drafts-bailleur-prevenu',
    description: 'Remind usagers with pending drafts when blocked with bailleur non prévenu',
)]
class RemindPendingDraftsCommand extends AbstractCronCommand
{
    public function __construct(
        private readonly SignalementDraftRepository $signalementDraftRepository,
        private readonly SignalementDraftManager $signalementDraftManager,
        private readonly SignalementDraftHelper $signalementDraftHelper,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // histologe is the name of the production scalingo app
        // test is injected in RemindPendingDraftsCommandTest
        // dev is for local development
        if ('histologe' !== getenv('APP') && 'test' !== getenv('APP') && 'dev' !== $_ENV['APP_ENV']) {
            $io->error('This command is only available on production environment, test environment and dev environment');

            return Command::FAILURE;
        }

        $signalementDrafts = $this->signalementDraftRepository->findPendingBlockedBailLast3Months();

        $count = 0;
        /** @var SignalementDraft $signalementDraft */
        foreach ($signalementDrafts as $signalementDraft) {
            if ($this->signalementDraftHelper->isPublicAndBailleurPrevenuPeriodPassed($signalementDraft)) {
                ++$count;
                $this->notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_SIGNALEMENT_DRAFT_PENDING_BAILLEUR_PREVENU,
                        to: $signalementDraft->getEmailDeclarant(),
                        signalementDraft: $signalementDraft,
                    )
                );
                $signalementDraft->setPendingDraftRemindedAtValue();
                $this->signalementDraftManager->save($signalementDraft);
            }
        }

        $io->success(\sprintf('%s usagers have been notified', $count));

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: $this->parameterBag->get('admin_email'),
                message: $count > 1 ? 'usagers ont été notifiés' : 'usager a été notifié',
                cronLabel: 'brouillon en attente de bailleur prévenu',
                cronCount: $count,
            )
        );

        return Command::SUCCESS;
    }
}
