<?php

namespace App\Command\Cron;

use App\Entity\Suivi;
use App\Factory\SuiviFactory;
use App\Manager\SuiviManager;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:ask-feedback-usager',
    description: 'Ask feedback to usager if no suivi from 30 days',
)]
class AskFeedbackUsagerCommand extends AbstractCronCommand
{
    private SymfonyStyle $io;
    private const FLUSH_COUNT = 1000;

    public function __construct(
        private readonly SuiviManager $suiviManager,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
        private readonly SuiviRepository $suiviRepository,
        private readonly SignalementRepository $signalementRepository,
        private readonly SuiviFactory $suiviFactory,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function configure(): void
    {
        $this->addOption(
            '--debug',
            null,
            InputOption::VALUE_NONE,
            'Check how many emails will be send'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $nbSignalementsThirdRelance = $this->processSignalementsThirdRelance($input);
        $nbSignalementsLastSuiviTechnical = $this->processSignalementsLastSuiviTechnical($input);
        $nbSignalementsLastSuiviPublic = $this->processSignalementsLastSuiviPublic($input);

        $nbSignalements = $nbSignalementsThirdRelance + $nbSignalementsLastSuiviTechnical + $nbSignalementsLastSuiviPublic;
        if ($input->getOption('debug')) {
            $nbSignalementsForDebug = ($nbSignalementsLastSuiviPublic - $nbSignalementsLastSuiviTechnical)
            + ($nbSignalementsLastSuiviTechnical - $nbSignalementsThirdRelance)
            + $nbSignalementsThirdRelance;
            $this->io->info(\sprintf(
                '%s signalement(s) for which a request for feedback will be sent to the user',
                $nbSignalementsForDebug
            ));

            return Command::SUCCESS;
        }

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: $this->parameterBag->get('admin_email'),
                message: \sprintf(
                    '%s signalement(s) pour lesquels une demande de feedback a été envoyée à l\'usager répartis comme suit :
                    %s dont les deux derniers suivis sont des suivis techniques demande de feedback et le dernier a plus de '.Suivi::DEFAULT_PERIOD_INACTIVITY.' jours,
                    %s dont le dernier suivi est un suivi technique demande de feedback et date de plus de '.Suivi::DEFAULT_PERIOD_INACTIVITY.' jours,
                    %s dont le dernier suivi public a plus de '.Suivi::DEFAULT_PERIOD_RELANCE.' jours. ',
                    $nbSignalements,
                    $nbSignalementsThirdRelance,
                    $nbSignalementsLastSuiviTechnical,
                    $nbSignalementsLastSuiviPublic,
                ),
                cronLabel: 'demande de feedback à l\'usager',
            )
        );

        return Command::SUCCESS;
    }

    protected function processSignalementsThirdRelance(
        InputInterface $input,
    ): int {
        $signalementsIds = $this->suiviRepository->findSignalementsForThirdRelance();
        $nbSignalements = $this->sendMailToUsagers(
            $input,
            $signalementsIds,
            NotificationMailerType::TYPE_SIGNALEMENT_FEEDBACK_USAGER_THIRD
        );
        if (!$input->getOption('debug')) {
            $this->io->success(\sprintf(
                '%s signalement(s) for which the two last suivis are technicals and the last one is older than '
                .Suivi::DEFAULT_PERIOD_INACTIVITY.' days',
                $nbSignalements
            ));
        }

        return $nbSignalements;
    }

    protected function processSignalementsLastSuiviTechnical(
        InputInterface $input,
    ): int {
        $signalementsIds = $this->suiviRepository->findSignalementsLastSuiviTechnical();
        $nbSignalements = $this->sendMailToUsagers(
            $input,
            $signalementsIds,
            NotificationMailerType::TYPE_SIGNALEMENT_FEEDBACK_USAGER_WITHOUT_RESPONSE
        );
        if (!$input->getOption('debug')) {
            $this->io->success(\sprintf(
                '%s signalement(s) for which the last suivi is technical and is older than '
                .Suivi::DEFAULT_PERIOD_INACTIVITY.' days',
                $nbSignalements
            ));
        }

        return $nbSignalements;
    }

    protected function processSignalementsLastSuiviPublic(
        InputInterface $input,
    ): int {
        $signalementsIds = $this->suiviRepository->findSignalementsLastSuiviPublic();
        $nbSignalements = $this->sendMailToUsagers(
            $input,
            $signalementsIds,
            NotificationMailerType::TYPE_SIGNALEMENT_FEEDBACK_USAGER_WITH_RESPONSE
        );
        if (!$input->getOption('debug')) {
            $this->io->success(\sprintf(
                '%s signalement(s) without suivi public from more than '.Suivi::DEFAULT_PERIOD_RELANCE.' days',
                $nbSignalements
            ));
        }

        return $nbSignalements;
    }

    protected function sendMailToUsagers(
        InputInterface $input,
        array $signalementsIds,
        NotificationMailerType $notificationMailerType
    ): int {
        $totalRead = 0;
        $signalements = $this->signalementRepository->findAllByIds($signalementsIds);
        $nbSignalements = \count($signalements);
        if ($input->getOption('debug')) {
            return $nbSignalements;
        }

        foreach ($signalements as $signalement) {
            ++$totalRead;
            $toRecipients = $signalement->getMailUsagers();
            if (!empty($toRecipients)) {
                foreach ($toRecipients as $toRecipient) {
                    $this->notificationMailerRegistry->send(
                        new NotificationMail(
                            type: $notificationMailerType,
                            to: $toRecipient,
                            territory: $signalement->getTerritory(),
                            signalement: $signalement,
                        )
                    );
                }
            }

            $params = [
                'type' => Suivi::TYPE_TECHNICAL,
                'description' => "Un message automatique a été envoyé à l'usager pour lui demander de mettre à jour sa situation.",
            ];

            $suivi = $this->suiviFactory->createInstanceFrom(
                user: null,
                signalement: $signalement,
                params: $params,
            );

            if (0 === $totalRead % self::FLUSH_COUNT) {
                $this->suiviManager->save($suivi);
            } else {
                $this->suiviManager->save($suivi, false);
            }
        }

        $this->suiviManager->flush();

        return $nbSignalements;
    }
}
