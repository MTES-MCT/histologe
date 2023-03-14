<?php

namespace App\Command;

use App\Manager\SignalementManager;
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
    public function __construct(
        private SignalementManager $signalementManager,
        private NotificationService $notificationService,
        private UrlGeneratorInterface $urlGenerator,
        private TokenGenerator $tokenGenerator,
        private ParameterBagInterface $parameterBag,
        private SuiviRepository $suiviRepository,
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
        $io = new SymfonyStyle($input, $output);

        // $signalementsList = $this->signalementManager->getRepository()->findSignalementWithSuiviOlderThan30();

        // TODO : revoir la récupération de la liste
        $signalements = $this->suiviRepository->findSignalementNoSuiviSince(30);

        $nbSignalements = \count($signalements);
        if ($input->getOption('debug')) {
            $io->info(sprintf('%s signalement without suivi from more than 30 days', $nbSignalements));

            return Command::SUCCESS;
        }

        foreach ($signalements as $signalement) {
            $toRecipients = $signalement->getMailUsagers();
            if (!empty($toRecipients)) {
                foreach ($toRecipients as $toRecipient) {
                    $this->notificationService->send(
                        NotificationService::TYPE_SIGNALEMENT_FEEDBACK_USAGER,
                        [$toRecipient],
                        [
                            'link' => $this->generateLinkCodeSuivi($signalement->getCodeSuivi(), $toRecipient),
                        ],
                        $signalement->getTerritory()
                    );
                }
            }
        }
        // TODO : comment prendre en compte cet envoi pour qu'il n'y en ait pas un autre avant 30 jours ?

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
