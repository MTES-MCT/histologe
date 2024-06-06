<?php

namespace App\Command\Cron;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:notify-and-archive-inactive-accounts',
    description: 'Sends notifications to inactive accounts and archives them after 30 days'
)]
class NotifyAndArchiveInactiveAccountCommand extends AbstractCronCommand
{
    private SymfonyStyle $io;

    public const NB_DAYS_FIRST_NOTIFICATION = 30;
    public const NB_DAYS_SECOND_NOTIFICATION = 7;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
        #[Autowire(env: 'FEATURE_ARCHIVE_INACTIVE_ACCOUNT')]
        private bool $featureArchiveInactiveAccount,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        if (!$this->featureArchiveInactiveAccount) {
            $this->io->warning('Feature "FEATURE_ARCHIVE_INACTIVE_ACCOUNT" is disabled.');

            return Command::SUCCESS;
        }

        $nbFirst = $this->sendFirstNotifications();
        $nbSecond = $this->sendSecondNotifications();
        $nbArchive = $this->archiveAccounts();

        $this->entityManager->flush();

        $message = $nbFirst.' premières notifications de compte inactif envoyées. ';
        $message .= $nbSecond.' secondes notifications de compte inactif envoyées. ';
        $message .= $nbArchive.' comptes inactifs archivés.';

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: $this->parameterBag->get('admin_email'),
                message: $message,
                cronLabel: 'Notification de compte inactif',
            )
        );

        return Command::SUCCESS;
    }

    private function sendFirstNotifications(int $nbDays = self::NB_DAYS_FIRST_NOTIFICATION): int
    {
        $users = $this->userRepository->findInactiveUsers(isArchivingScheduled: false);

        foreach ($users as $user) {
            $user->setPassword('');
            $user->setArchivingScheduledAt(new \DateTimeImmutable('+'.$nbDays.' days'));
            $this->sendNotification($user, $nbDays);
        }

        $this->io->success(\count($users).' first notifications sent to inactive users.');

        return \count($users);
    }

    private function sendSecondNotifications(int $nbDays = self::NB_DAYS_SECOND_NOTIFICATION): int
    {
        $date = new \DateTimeImmutable('+'.$nbDays.' days');
        $users = $this->userRepository->findInactiveUsers(archivingScheduledAt: $date);

        foreach ($users as $user) {
            $this->sendNotification($user, $nbDays);
        }

        $this->io->success(\count($users).' second notifications sent to inactive users.');

        return \count($users);
    }

    private function archiveAccounts(): int
    {
        $users = $this->userRepository->findUsersToArchive();

        foreach ($users as $user) {
            $user->setStatut(User::STATUS_ARCHIVE);
            $user->setArchivingScheduledAt(null);
        }

        $this->io->success(\count($users).' accounts archived.');

        return \count($users);
    }

    private function sendNotification(User $user, int $nbDays)
    {
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_ACCOUNT_SOON_ARCHIVED,
                to: $user->getEmail(),
                params: ['nbDays' => $nbDays],
            )
        );
    }
}
