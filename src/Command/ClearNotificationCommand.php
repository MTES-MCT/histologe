<?php

namespace App\Command;

use App\Entity\Notification;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:clear-notification',
    description: 'Clear notification older than 30 days',
)]
class ClearNotificationCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationMailerRegistry $notificationMailerRegistry,
        private ParameterBagInterface $parameterBag
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $notifications = $this->entityManager->getRepository(Notification::class)->findOlderThan(30);
        foreach ($notifications as $notification) {
            $this->entityManager->remove($notification);
        }
        $this->entityManager->flush();

        $nbNotifications = \count($notifications);
        $io->success($nbNotifications.' notification(s) deleted !');

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                NotificationMailerType::TYPE_CRON,
                $this->parameterBag->get('admin_email'),
                [
                    'cron_label' => 'Suppression des notifications',
                    'count' => $nbNotifications,
                    'message' => $nbNotifications > 1 ? 'notifications ont été supprimées' : 'notification a été supprimée',
                ],
                null
            )
        );

        return Command::SUCCESS;
    }
}
