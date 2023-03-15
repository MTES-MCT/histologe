<?php

namespace App\Command;

use App\Entity\Suivi;
use App\Manager\SuiviManager;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Service\NotificationService;
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
    name: 'app:ask-feeback-usager',
    description: 'Ask feedback to usager if no suivi from 30 days',
)]
class AskFeedbackUsagerCommand extends Command
{
    private const FLUSH_COUNT = 1000;

    public function __construct(
        private SuiviManager $suiviManager,
        private NotificationService $notificationService,
        private UrlGeneratorInterface $urlGenerator,
        private TokenGenerator $tokenGenerator,
        private ParameterBagInterface $parameterBag,
        private SuiviRepository $suiviRepository,
        private SignalementRepository $signalementRepository,
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

        $signalementsIds = $this->suiviRepository->findSignalementsNoSuiviUsagerFrom(30);
        $signalements = $this->signalementRepository->findAllByIds($signalementsIds);
        $nbSignalements = \count($signalements);
        if ($input->getOption('debug')) {
            $io->info(sprintf('%s signalement without suivi from more than 30 days', $nbSignalements));

            return Command::SUCCESS;
        }

        foreach ($signalements as $signalement) {
            ++$totalRead;
            $toRecipients = $signalement->getMailUsagers();
            if (!empty($toRecipients)) {
                foreach ($toRecipients as $toRecipient) {
                    $this->notificationService->send(
                        NotificationService::TYPE_SIGNALEMENT_FEEDBACK_USAGER,
                        [$toRecipient],
                        [
                            'signalement' => $signalement,
                            'lien_suivi' => $this->generateLinkCodeSuivi($signalement->getCodeSuivi(), $toRecipient),
                        ],
                        $signalement->getTerritory()
                    );
                }
            }

            $suivi = new Suivi();
            $suivi->setSignalement($signalement);
            $suivi->setDescription("Un message automatique a été envoyé à l'usager pour lui demander de mettre à jour sa situation.");
            $suivi->setIsPublic(false);
            $suivi->setType(SUIVI::TYPE_TECHNICAL);

            if (0 === $totalRead % self::FLUSH_COUNT) {
                $this->suiviManager->save($suivi);
            } else {
                $this->suiviManager->save($suivi, false);
            }
        }

        $this->suiviManager->flush();

        $io->success(sprintf('%s signalement without suivi from more than 30 days', $nbSignalements));

        $this->notificationService->send(
            NotificationService::TYPE_CRON,
            $this->parameterBag->get('admin_email'),
            [
                'url' => $this->parameterBag->get('host_url'),
                'cron_label' => 'demande de feedback à l\'usager',
                'count' => $nbSignalements,
                'message' => 'signalement(s) pour lesquels une demande de feedback a été envoyée à l\'usager',
            ],
            null
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
