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

        // on récupère les signalements dont les deux derniers suivis sont des suivis techniques demande de feedback
        // et dont le dernier suivi a plus de 30 jours
        $signalementsIdsThirdRelance = $this->suiviRepository->findSignalementsThirdRelance();
        $nbSignalementsThirdRelance = $this->sendMailToUsagers(
            $input,
            $signalementsIdsThirdRelance,
            NotificationMailerType::TYPE_SIGNALEMENT_FEEDBACK_USAGER_THIRD
        );
        $this->io->success(sprintf(
            '%s signalement(s) with two last suivis are technicals and last one older than '.Suivi::DEFAULT_PERIOD_INACTIVITY.' days',
            $nbSignalementsThirdRelance
        ));

        // on récupère ensuite les signalements dont le dernier suivi est un suivi technique demande de feedback et date de plus de 30 jours
        $signalementsIdsLastSuiviTechnical = $this->suiviRepository->findSignalementsWithLastSuiviTechnical();
        $nbSignalementsLastSuiviTechnical = $this->sendMailToUsagers($input, $signalementsIdsLastSuiviTechnical);
        $this->io->success(sprintf(
            '%s signalement(s) with last suivi technical and older than '.Suivi::DEFAULT_PERIOD_INACTIVITY.' days',
            $nbSignalementsLastSuiviTechnical
        ));

        // on récupère enfin les signalements dont le dernier suivi public a plus de 45 jours (et le dernier suivi technique aussi)
        $signalementsIdsLastSuiviPublic = $this->suiviRepository->findSignalementsNoSuiviUsagerFrom();
        $nbSignalementsLastSuiviPublic = $this->sendMailToUsagers($input, $signalementsIdsLastSuiviPublic);
        $this->io->success(sprintf(
            '%s signalement(s) without suivi public from more than '.Suivi::DEFAULT_PERIOD_RELANCE.' days',
            $nbSignalementsLastSuiviPublic
        ));

        $nbSignalements = $nbSignalementsThirdRelance + $nbSignalementsLastSuiviTechnical + $nbSignalementsLastSuiviPublic;
        if ($input->getOption('debug')) {
            $this->io->info(sprintf(
                '%s signalement(s) for which a request for feedback will be sent to the user',
                $nbSignalements
            ));

            return Command::SUCCESS;
        }

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: $this->parameterBag->get('admin_email'),
                message: sprintf(
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

    protected function sendMailToUsagers(
        InputInterface $input,
        array $signalementsIds,
        NotificationMailerType $notificationMailerType = NotificationMailerType::TYPE_SIGNALEMENT_FEEDBACK_USAGER
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
                if (null === $signalement->getCodeSuivi()) {
                    $signalement->setCodeSuivi(md5(uniqid()));
                }
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
                'type' => SUIVI::TYPE_TECHNICAL,
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
