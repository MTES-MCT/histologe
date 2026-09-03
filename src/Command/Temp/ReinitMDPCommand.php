<?php

namespace App\Command\Temp;

use App\Entity\Enum\UserStatus;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
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
        private readonly EntityManagerInterface $entityManager,
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

        // Désactivation des utilisateurs actifs pour éviter qu'ils puissent se connecter avec leur ancien mot de passe
        $activeUsers = array_filter(
            $firstUsers,
            static fn (User $user): bool => UserStatus::ACTIVE === $user->getStatut()
        );
        $io->info(sprintf('Users to inactive: %s', \count($activeUsers)));
        $progressBar = new ProgressBar($output, \count($activeUsers));
        $progressBar->start();
        foreach ($activeUsers as $user) {
            $user->setStatut(UserStatus::INACTIVE);
            $this->entityManager->persist($user);
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->entityManager->flush();

        // Envoi des emails de réinitialisation de mot de passe aux utilisateurs inactifs n'ayant pas de mot de passe défini
        $needEmailUsers = array_filter(
            $firstUsers,
            static fn (User $user): bool => UserStatus::ARCHIVE !== $user->getStatut() && null !== $user->getPassword()
        );

        $io->info(sprintf('Users found: %s', \count($needEmailUsers)));
        $nbMails = 0;
        $progressBar = new ProgressBar($output, \count($needEmailUsers));
        $progressBar->start();
        foreach ($needEmailUsers as $user) {
            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_TEMP_REINIT_MDP,
                    to: $user->getEmail(),
                    user: $user
                )
            );
            ++$nbMails;
            $progressBar->advance();
        }
        $progressBar->finish();
        $io->success(sprintf('Sent %d reinitialization emails.', $nbMails));

        return Command::SUCCESS;
    }
}
