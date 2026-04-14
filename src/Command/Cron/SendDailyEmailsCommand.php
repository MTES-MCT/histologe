<?php

namespace App\Command\Cron;

use App\Repository\ClubEventRepository;
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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'app:send-daily-emails',
    description: 'Sends summary emails and club event emails to users.',
)]
class SendDailyEmailsCommand extends AbstractCronCommand
{
    private const int NB_DAYS_BEFORE_CLUB_FIRST_MAIL = 7;
    private const int NB_DAYS_BEFORE_CLUB_SECOND_MAIL = 2;

    private SymfonyStyle $io;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ClubEventRepository $clubEventRepository,
        private readonly SummaryMailService $summaryMailService,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        // histologe is the name of the production scalingo app
        // test is injected in SendSummaryEmailsCommandTest
        // dev is for local development
        if ('histologe' !== getenv('APP') && 'test' !== getenv('APP') && 'dev' !== $_ENV['APP_ENV']) {
            $this->io->error('This command is only available on production environment, test environment and dev environment');

            return Command::FAILURE;
        }
        $this->sendSummaryEmails();
        $this->sendClubEventEmails(self::NB_DAYS_BEFORE_CLUB_FIRST_MAIL);
        $this->sendClubEventEmails(self::NB_DAYS_BEFORE_CLUB_SECOND_MAIL);

        return Command::SUCCESS;
    }

    private function sendSummaryEmails(): void
    {
        $this->io->section('Envoi des emails récapitulatifs');
        $users = $this->userRepository->findUserWaitingSummaryEmail();
        $progressBar = $this->io->createProgressBar(\count($users));
        $progressBar->start();

        $nbMails = 0;
        foreach ($users as $user) {
            $nbMails += $this->summaryMailService->sendSummaryEmailIfNeeded($user);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->io->newLine(2);
        $message = $nbMails.' emails récapitulatifs envoyés.';
        $this->io->success($message);

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: (string) $this->parameterBag->get('admin_email'),
                message: $message,
                cronLabel: 'Emails récapitulatifs'
            )
        );
    }

    private function sendClubEventEmails(int $forEventInNbDays): void
    {
        $this->io->section('Envoi des emails des clubs pour les évènements à moins de '.$forEventInNbDays.' jours');
        $clubs = $this->clubEventRepository->findInExactlyNbDays($forEventInNbDays);
        $mailType = self::NB_DAYS_BEFORE_CLUB_FIRST_MAIL === $forEventInNbDays ? NotificationMailerType::TYPE_CLUB_EVENT : NotificationMailerType::TYPE_CLUB_EVENT_REMINDER;
        $nbMails = 0;
        foreach ($clubs as $club) {
            $users = $this->userRepository->findUsersToNotifyForClubEvent($club);
            foreach ($users as $user) {
                $this->notificationMailerRegistry->send(
                    new NotificationMail(
                        type: $mailType,
                        to: $user->getEmail(),
                        params: [
                            'name' => $club->getName(),
                            'date' => $club->getDateEvent()->format('d/m/Y'),
                            'hour' => $club->getDateEvent()->format('H:i'),
                            'url' => $club->getUrl(),
                        ],
                    )
                );
                ++$nbMails;
            }
        }
        $message = $nbMails.' emails de clubs envoyés à moins '.$forEventInNbDays.' jours.';
        $this->io->success($message);
        if ($nbMails) {
            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_CRON,
                    to: (string) $this->parameterBag->get('admin_email'),
                    message: $message,
                    cronLabel: 'Emails des clubs'
                )
            );
        }
    }
}
