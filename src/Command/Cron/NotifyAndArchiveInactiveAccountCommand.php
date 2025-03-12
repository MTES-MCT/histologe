<?php

namespace App\Command\Cron;

use App\Entity\User;
// use App\Manager\HistoryEntryManager;
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

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        // private readonly HistoryEntryManager $historyEntryManager,
        private readonly ClockInterface $clock,
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
        // $this->historyEntryManager->removeEntityListeners();
        $this->io = new SymfonyStyle($input, $output);
        $message = '';
        $nbScheduled = 0;
        if (!$this->featureArchiveInactiveAccount) {
            $this->io->warning('Feature "FEATURE_ARCHIVE_INACTIVE_ACCOUNT" is disabled.');

            return Command::SUCCESS;
        }

        if ('15' === $this->clock->now()->format('d')) {
            $nbScheduled = $this->archiveSchedulingAndSendRtNotifications();
            $message = $nbScheduled.' comptes inactifs mis en instance d\'archivage.';
        }
        $nbArchived = $this->archiveAccounts();
        $this->entityManager->flush();

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

    private function archiveSchedulingAndSendRtNotifications(): int
    {
        $users = $this->userRepository->findInactiveUsers();
        $pendingUsersByTerritories = [];
        foreach ($users as $user) {
            $user->setPassword('');
            $user->setArchivingScheduledAt(new \DateTimeImmutable('+15 days'));
            foreach ($user->getPartnersTerritories() as $territory) {
                $pendingUsersByTerritories[$territory->getId()][] = $user;
            }
        }
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

        foreach ($users as $user) {
            $user->setEmail(Sanitizer::tagArchivedEmail($user->getEmail()));
            $user->setStatut(User::STATUS_ARCHIVE);
            $user->setArchivingScheduledAt(null);
        }

        $this->io->success(\count($users).' accounts archived.');

        return \count($users);
    }

    private function sendRtNotification(array $adminsList, array $usersList)
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
}
