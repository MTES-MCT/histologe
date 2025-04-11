<?php

namespace App\Command\Cron;

use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Mailer\SummaryMailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:send-summary-emails',
    description: 'Sends summary emails to users'
)]
class SendSummaryEmailsCommand extends AbstractCronCommand
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly SummaryMailService $summaryMailService,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
        #[Autowire(env: 'FEATURE_EMAIL_RECAP')]
        private bool $featureEmailRecap,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        if (!$this->featureEmailRecap) {
            $this->io->warning('Feature "FEATURE_EMAIL_RECAP" is disabled.');

            return Command::SUCCESS;
        }

        $users = $this->userRepository->findUserWaitingSummaryEmail();
        $nbMails = 0;
        foreach ($users as $user) {
            $nbMails += $this->summaryMailService->sendSummaryEmailIfNeeded($user);
        }

        $message = $nbMails.' emails récapitulatifs envoyés.';
        $this->io->success($message);

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: $this->parameterBag->get('admin_email'),
                message: $message,
                cronLabel: 'Emails récapitulatifs'
            )
        );

        return Command::SUCCESS;
    }
}
