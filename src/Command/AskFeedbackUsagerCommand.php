<?php

namespace App\Command;

use App\Entity\Suivi;
use App\Factory\SuiviFactory;
use App\Manager\SuiviManager;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Token\TokenGenerator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'app:ask-feedback-usager',
    description: 'Ask feedback to usager if no suivi from 30 days',
)]
class AskFeedbackUsagerCommand extends Command
{
    private const FLUSH_COUNT = 1000;

    public function __construct(
        private SuiviManager $suiviManager,
        private NotificationMailerRegistry $notificationMailerRegistry,
        private UrlGeneratorInterface $urlGenerator,
        private TokenGenerator $tokenGenerator,
        private ParameterBagInterface $parameterBag,
        private SuiviRepository $suiviRepository,
        private SignalementRepository $signalementRepository,
        private SuiviFactory $suiviFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('--debug', null, InputOption::VALUE_NONE, 'Check how many emails will be send')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $totalRead = 0;
        $io = new SymfonyStyle($input, $output);

        $signalementsIds = $this->suiviRepository->findSignalementsNoSuiviUsagerFrom();
        $signalements = $this->signalementRepository->findAllByIds($signalementsIds);
        $nbSignalements = \count($signalements);
        if ($input->getOption('debug')) {
            $io->info(sprintf('%s signalement without suivi from more than '.Suivi::DEFAULT_PERIOD_INACTIVITY.' days', $nbSignalements));

            return Command::SUCCESS;
        }

        foreach ($signalements as $signalement) {
            ++$totalRead;
            $toRecipients = $signalement->getMailUsagers();
            if (!empty($toRecipients)) {
                if (null !== $signalement->getCodeSuivi()) {
                    $signalement->setCodeSuivi(md5(uniqid()));
                }
                foreach ($toRecipients as $toRecipient) {
                    $this->notificationMailerRegistry->send(
                        new NotificationMail(
                            type: NotificationMailerType::TYPE_SIGNALEMENT_FEEDBACK_USAGER,
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
                isPublic: false,
            );

            if (0 === $totalRead % self::FLUSH_COUNT) {
                $this->suiviManager->save($suivi);
            } else {
                $this->suiviManager->save($suivi, false);
            }
        }

        $this->suiviManager->flush();

        $io->success(sprintf('%s signalement without suivi from more than '.Suivi::DEFAULT_PERIOD_INACTIVITY.' days', $nbSignalements));

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: $this->parameterBag->get('admin_email'),
                message: 'signalement(s) pour lesquels une demande de feedback a été envoyée à l\'usager',
                cronLabel: 'demande de feedback à l\'usager',
                cronCount: $nbSignalements,
            )
        );

        return Command::SUCCESS;
    }

    private function generateLinkCodeSuivi(string $codeSuivi, string $email): string
    {
        return $this->parameterBag->get('host_url').$this->urlGenerator->generate(
            'front_suivi_signalement',
            [
                'code' => $codeSuivi,
                'from' => $email,
            ]
        );
    }
}
