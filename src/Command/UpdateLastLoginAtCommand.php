<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user-update-last-login-at',
    description: 'Met à jour lastLoginAt selon le dernier LOGIN trouvé dans history_entry',
)]
class UpdateLastLoginAtCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Afficher les utilisateurs concernés sans mettre à jour la base'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $conn = $this->entityManager->getConnection();

        $sql = <<<SQL
            SELECT u.id, u.email, u.last_login_at, MAX(h.created_at) AS last_login_event
            FROM user u
            JOIN history_entry h ON h.user_id = u.id
            WHERE h.event = 'LOGIN'
            GROUP BY u.id, u.email, u.last_login_at
            HAVING u.last_login_at IS NULL OR u.last_login_at < MAX(h.created_at)
        SQL;

        $rows = $conn->fetchAllAssociative($sql);

        if (empty($rows)) {
            $io->success('Aucune mise à jour nécessaire 🎉');

            return Command::SUCCESS;
        }

        $io->section(sprintf('%d utilisateurs à mettre à jour', count($rows)));

        if ($dryRun) {
            $io->table(['ID', 'Email', 'Ancien lastLoginAt', 'Dernier LOGIN'], array_map(static fn ($row) => [
                $row['id'],
                $row['email'],
                $row['last_login_at'],
                $row['last_login_event'],
            ], $rows));

            $io->success('Mode dry-run : aucune donnée modifiée');

            return Command::SUCCESS;
        }

        $updated = 0;
        foreach ($rows as $row) {
            $user = $this->entityManager->getRepository(User::class)->find($row['id']);
            if ($user) {
                $user->setLastLoginAt(new \DateTimeImmutable($row['last_login_event']));
                ++$updated;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d utilisateurs mis à jour ✅', $updated));

        return Command::SUCCESS;
    }
}
