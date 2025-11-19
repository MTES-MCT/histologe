<?php

namespace App\Command\Cron;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Manager\SuiviManager;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\DBAL\Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:ask-feedback-usager',
    description: 'Ask feedback to usagers on signalements that have been inactive for a certain period',
)]
class AskFeedbackUsagerCommand extends AbstractCronCommand
{
    private SymfonyStyle $io;
    private const int FLUSH_COUNT = 1000;
    public const string FIRST_RELANCE_LOG_MESSAGE = 'signalement(s) en première relance (dont le dernier suivi public a plus de '.Suivi::DEFAULT_PERIOD_RELANCE.' jours)';
    public const string SECOND_RELANCE_LOG_MESSAGE = 'signalement(s) en 2è relance (dont le dernier suivi est un suivi technique demande de feedback et date de plus de '.Suivi::DEFAULT_PERIOD_INACTIVITY.' jours)';
    public const string THIRD_RELANCE_LOG_MESSAGE = 'signalement(s) en 3è relance (dont les deux derniers suivis sont des suivis techniques demande de feedback et le dernier a plus de '.Suivi::DEFAULT_PERIOD_INACTIVITY.' jours)';
    public const string LOOP_LOG_MESSAGE = 'signalement(s) en phase “boucle” (dont les trois derniers suivis sont des suivis techniques demande de feedback et le dernier a plus de '.Suivi::DEFAULT_PERIOD_BOUCLE.' jours)';

    public function __construct(
        private readonly SuiviManager $suiviManager,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
        private readonly SuiviRepository $suiviRepository,
        private readonly SignalementRepository $signalementRepository,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function configure(): void
    {
        $this->addOption(
            '--debug',
            null,
            InputOption::VALUE_NONE,
            'Check how many emails will be sent'
        );
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        // histologe is the name of the production scalingo app
        // test is injected in AskFeedbackUsagerCommandTest
        // dev is for local development
        if ('histologe' !== getenv('APP') && 'test' !== getenv('APP') && 'dev' !== $_ENV['APP_ENV']) {
            $this->io->error('This command is only available on production environment, test environment and dev environment');

            return Command::FAILURE;
        }
        // Warning : order matters !
        $nbSignalementsLoopRelance = $this->processSignalementsLoopRelance($input);
        $nbSignalementsThirdRelance = $this->processSignalementsThirdRelance($input);
        $nbSignalementsSecondRelance = $this->processSignalementsSecondRelance($input);
        $nbSignalementsFirstRelance = $this->processSignalementsFirstRelance($input);

        if ($input->getOption('debug')) {
            // as in debug mode we do not create suivis, we need to adjust the counts to avoid double counting
            $nbSignalementsFirstRelanceForDebug = $nbSignalementsFirstRelance - $nbSignalementsSecondRelance;
            $nbSignalementsSecondRelanceForDebug = $nbSignalementsSecondRelance - $nbSignalementsThirdRelance - $nbSignalementsLoopRelance;
            $nbSignalementsThirdRelanceForDebug = $nbSignalementsThirdRelance;
            $nbSignalementsLoopRelanceForDebug = $nbSignalementsLoopRelance;

            $nbSignalementsForDebug = $nbSignalementsThirdRelanceForDebug + $nbSignalementsSecondRelanceForDebug + $nbSignalementsFirstRelanceForDebug + $nbSignalementsLoopRelanceForDebug;
            $this->io->info(\sprintf(
                '%s signalement(s) pour lesquels une demande de feedback sera envoyée à l\'usager répartis comme suit :
                    %s '.self::FIRST_RELANCE_LOG_MESSAGE.', 
                    %s '.self::SECOND_RELANCE_LOG_MESSAGE.',
                    %s '.self::THIRD_RELANCE_LOG_MESSAGE.',
                    %s '.self::LOOP_LOG_MESSAGE,
                $nbSignalementsForDebug,
                $nbSignalementsFirstRelanceForDebug,
                $nbSignalementsSecondRelanceForDebug,
                $nbSignalementsThirdRelanceForDebug,
                $nbSignalementsLoopRelanceForDebug
            ));

            return Command::SUCCESS;
        }

        $nbSignalements = $nbSignalementsThirdRelance + $nbSignalementsSecondRelance + $nbSignalementsFirstRelance + $nbSignalementsLoopRelance;
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: (string) $this->parameterBag->get('admin_email'),
                message: \sprintf(
                    '%s signalement(s) pour lesquels une demande de feedback a été envoyée à l\'usager répartis comme suit :<ul>
                        <li>%s '.self::FIRST_RELANCE_LOG_MESSAGE.',</li>
                        <li>%s '.self::SECOND_RELANCE_LOG_MESSAGE.',</li>
                        <li>%s '.self::THIRD_RELANCE_LOG_MESSAGE.',</li>
                        <li>%s '.self::LOOP_LOG_MESSAGE.'.</li></ul>',
                    $nbSignalements,
                    $nbSignalementsFirstRelance,
                    $nbSignalementsSecondRelance,
                    $nbSignalementsThirdRelance,
                    $nbSignalementsLoopRelance
                ),
                cronLabel: 'demande de feedback à l\'usager',
            )
        );

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    protected function processSignalementsLoopRelance(
        InputInterface $input,
    ): int {
        $signalementsIds = $this->suiviRepository->findSignalementsForLoopAskFeedbackRelance();
        $nbSignalements = $this->sendMailAndCreateSuiviIfNoDebug(
            $input,
            $signalementsIds,
            NotificationMailerType::TYPE_SIGNALEMENT_FEEDBACK_USAGER_THIRD
        );
        if (!$input->getOption('debug')) {
            $this->io->success(sprintf(
                '%s '.self::LOOP_LOG_MESSAGE,
                $nbSignalements
            ));
        }

        return $nbSignalements;
    }

    /**
     * @throws Exception
     */
    protected function processSignalementsThirdRelance(
        InputInterface $input,
    ): int {
        $signalementsIds = $this->suiviRepository->findSignalementsForThirdAskFeedbackRelance();
        $nbSignalements = $this->sendMailAndCreateSuiviIfNoDebug(
            $input,
            $signalementsIds,
            NotificationMailerType::TYPE_SIGNALEMENT_FEEDBACK_USAGER_THIRD
        );
        if (!$input->getOption('debug')) {
            $this->io->success(\sprintf(
                '%s '.self::THIRD_RELANCE_LOG_MESSAGE,
                $nbSignalements
            ));
        }

        return $nbSignalements;
    }

    /**
     * @throws Exception
     */
    protected function processSignalementsSecondRelance(
        InputInterface $input,
    ): int {
        $signalementsIds = $this->suiviRepository->findSignalementsLastAskFeedbackSuiviTechnical();
        $nbSignalements = $this->sendMailAndCreateSuiviIfNoDebug(
            $input,
            $signalementsIds,
            NotificationMailerType::TYPE_SIGNALEMENT_FEEDBACK_USAGER_WITHOUT_RESPONSE
        );
        if (!$input->getOption('debug')) {
            $this->io->success(\sprintf(
                '%s '.self::SECOND_RELANCE_LOG_MESSAGE,
                $nbSignalements
            ));
        }

        return $nbSignalements;
    }

    /**
     * @throws Exception
     */
    protected function processSignalementsFirstRelance(
        InputInterface $input,
    ): int {
        $signalementsIds = $this->suiviRepository->findSignalementsLastSuiviPublic();
        $nbSignalements = $this->sendMailAndCreateSuiviIfNoDebug(
            $input,
            $signalementsIds,
            NotificationMailerType::TYPE_SIGNALEMENT_FEEDBACK_USAGER_WITH_RESPONSE
        );
        if (!$input->getOption('debug')) {
            $this->io->success(\sprintf(
                '%s '.self::FIRST_RELANCE_LOG_MESSAGE,
                $nbSignalements
            ));
        }

        return $nbSignalements;
    }

    /** @param array<int, int|string> $signalementsIds */
    protected function sendMailAndCreateSuiviIfNoDebug(
        InputInterface $input,
        array $signalementsIds,
        NotificationMailerType $notificationMailerType,
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

            $suivi = $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: "Un message automatique a été envoyé à l'usager pour lui demander de mettre à jour sa situation.",
                type: Suivi::TYPE_TECHNICAL,
                category: SuiviCategory::ASK_FEEDBACK_SENT,
                flush: false
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
