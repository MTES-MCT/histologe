<?php

namespace App\Command\Temp;

use App\Entity\JobEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-job-event-errors',
    description: 'Update JobEvent response_has_errors column based on response content',
)]
class UpdateJobEventErrorsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $connection = $this->entityManager->getConnection();

        $io->info('Updating job_event table. This might take a while on large datasets.');

        $sql = 'UPDATE job_event SET is_operational_error = 1 WHERE ';
        $conditions = [];
        foreach (JobEvent::OPERATIONAL_ERRORS as $operationalError) {
            $conditions[] = sprintf("response LIKE '%s%%'", $operationalError);
        }
        $sql .= implode(' OR ', $conditions);

        try {
            $result = $connection->executeStatement($sql);
            $io->success(sprintf('Updated %d rows.', $result));
        } catch (\Throwable $e) {
            $io->error('An error occurred: '.$e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
