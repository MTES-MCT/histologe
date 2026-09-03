<?php

namespace App\Command\Temp;

use App\Entity\Enum\UserStatus;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-reinit-mdp',
    description: 'Send email to users to reinitialize their password',
)]
class ReinitMDPCommand extends Command
{
    private const int LIMIT = 2000;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Sending reinitialization emails. This might take a while on large datasets.');

        $firstUsers = $this->userRepository->createQueryBuilder('u')
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(self::LIMIT)
            ->getQuery()
            ->getResult();

        $activedUsers = array_filter(
            $firstUsers,
            static fn (User $user): bool => UserStatus::ARCHIVE !== $user->getStatut() && null !== $user->getPassword()
        );

        $io->info(sprintf('Users found: %s', \count($activedUsers)));
        $nbMails = 0;
        foreach ($activedUsers as $user) {
            $io->info(sprintf('Sending email to user: %s', $user->getEmail()));

            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_TEMP_REINIT_MDP,
                    to: $user->getEmail(),
                    user: $user
                )
            );
            ++$nbMails;
        }

        $io->success(sprintf('Sent %d reinitialization emails.', $nbMails));

        return Command::SUCCESS;
    }
}
