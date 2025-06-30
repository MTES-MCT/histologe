<?php

namespace App\Command\Cron;

use App\Entity\Enum\UserStatus;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Sanitizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:notify-and-archive-inactive-accounts',
    description: 'Sends notifications to inactive accounts and archives them after 30 days'
)]
class NotifyAndArchiveInactiveAccountCommand extends AbstractCronCommand
{
    private SymfonyStyle $io;
    private const int BATCH_SIZE = 40;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ClockInterface $clock,
        private readonly UserRepository $userRepository,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_OPTIONAL, 'Force scheduled archiving accounts without checking date');
    }

    /**
     * @throws \DateMalformedStringException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        // histologe is the name of the production scalingo app
        // test is injected in NotifyAndArchiveInactiveAccountCommandTest
        if ('histologe' !== getenv('APP') && 'test' !== getenv('APP')) {
            $this->io->error('This command is only available on production environment');

            return Command::FAILURE;
        }

        $message = '';
        $nbScheduled = 0;

        if ('15' === $this->clock->now()->format('d') || $input->getOption('force')) {
            $nbScheduled = $this->scheduleArchivingAndSendRtNotification();
            $message = $nbScheduled.' comptes inactifs mis en instance d\'archivage.';
        }
        $nbArchived = $this->archiveAccounts();
        if ($nbArchived) {
            $message .= $nbArchived.' comptes inactifs archivés.';
        }

        if ($nbArchived || $nbScheduled) {
            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_CRON,
                    to: $this->parameterBag->get('admin_email'),
                    message: $message,
                    cronLabel: 'Notifications de comptes inactifs',
                )
            );
        }

        return Command::SUCCESS;
    }

    /**
     * @throws \DateMalformedStringException
     */
    private function scheduleArchivingAndSendRtNotification(): int
    {
        $users = $this->userRepository->findInactiveUsers();
        $pendingUsersByTerritories = [];
        $count = 0;

        $progressBar = $this->io->createProgressBar(\count($users));
        $progressBar->start();

        foreach ($users as $user) {
            $user->setPassword('');
            $user->setArchivingScheduledAt($this->clock->now()->modify('+15 days'));
            foreach ($user->getPartnersTerritories() as $territory) {
                $pendingUsersByTerritories[$territory->getId()][] = $user;
            }

            $this->sendUserNotification($user);

            ++$count;
            $progressBar->advance();

            if (0 === $count % self::BATCH_SIZE) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();
        $progressBar->finish();
        $this->io->newLine(2);

        foreach ($pendingUsersByTerritories as $territoryId => $pendingUsers) {
            $adminsToNotify = $this->userRepository->findActiveTerritoryAdmins($territoryId);
            $this->sendRtNotification($adminsToNotify, $pendingUsers);
        }

        $this->io->success(\count($users).' inactive accounts pending for archiving.');

        return \count($users);
    }

    private function archiveAccounts(): int
    {
        $users = $this->userRepository->findUsersToArchive();
        $count = 0;
        $progressBar = $this->io->createProgressBar(\count($users));
        $progressBar->start();

        foreach ($users as $user) {
            $user->setEmail(Sanitizer::tagArchivedEmail($user->getEmail()));
            $user->setStatut(UserStatus::ARCHIVE);
            $user->setArchivingScheduledAt(null);

            ++$count;
            $progressBar->advance();

            if (0 === $count % self::BATCH_SIZE) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();
        $progressBar->finish();
        $this->io->newLine(2);

        $this->io->success(\count($users).' accounts archived.');

        return \count($users);
    }

    /**
     * @param array<User> $adminsList
     * @param array<User> $usersList
     */
    private function sendRtNotification(array $adminsList, array $usersList): void
    {
        $territory = $adminsList[0]->getFirstTerritory();
        $adminMails = [];
        $usersData = [];
        foreach ($usersList as $user) {
            $usersData[] = [
                'email' => $user->getEmail(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'partenaire' => $user->getPartnerInTerritory($territory)->getNom(),
            ];
        }
        foreach ($adminsList as $admin) {
            $adminMails[] = $admin->getEmail();
        }
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_ACCOUNTS_SOON_ARCHIVED,
                to: $adminMails,
                territory: $territory,
                isRecipientVisible: false,
                params: ['usersData' => $usersData],
            )
        );
    }

    private function sendUserNotification(User $user): void
    {
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_ACCOUNT_USER_SOON_ARCHIVED,
                to: $user->getEmail(),
                isRecipientVisible: false,
            )
        );
    }
}
