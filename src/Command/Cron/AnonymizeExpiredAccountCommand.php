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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:anonymize-expired-account',
    description: 'Sends notifications to inactive accounts and archives them after 30 days'
)]
class AnonymizeExpiredAccountCommand extends AbstractCronCommand
{
    private SymfonyStyle $io;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $nbAgents = $this->anonymizeExpiredUsers();

        $this->entityManager->flush();

        $message = $nbAgents.' comptes agents expirés anonymisés.';

        if ($nbAgents > 0) {
            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_CRON,
                    to: $this->parameterBag->get('admin_email'),
                    message: $message,
                    cronLabel: 'Anonymisation de comptes expirés',
                )
            );
        }

        return Command::SUCCESS;
    }

    private function anonymizeExpiredUsers(): int
    {
        $expiredUsers = $this->userRepository->findExpiredUsers(true);

        /** @var User $user */
        foreach ($expiredUsers as $user) {
            $user->anonymize();
        }

        $this->io->success(\count($expiredUsers).' expired users anonymized.');

        return \count($expiredUsers);
    }
}
