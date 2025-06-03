<?php

namespace App\Command;

use App\Repository\InterventionRepository;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\DateParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-timezone-arrete-date-sish',
    description: 'One-time command to fix arrete date',
)]
class UpdateTimezoneArreteDateSISHCommand extends Command
{
    public const int BATCH_SIZE = 20;

    public function __construct(
        private readonly InterventionRepository $interventionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $interventions = $this->interventionRepository->findBy([
            'providerName' => 'esabora',
            'type' => 'ARRETE_PREFECTORAL',
        ]);

        $count = 0;
        $i = 0;
        $progressBar = new ProgressBar($output, \count($interventions));
        $progressBar->start();

        foreach ($interventions as $intervention) {
            $scheduledAt = $intervention->getScheduledAt();
            $signalement = $intervention->getSignalement();

            if (!$scheduledAt || !$signalement) {
                continue;
            }

            $timezone = $signalement->getTimezone() ?? 'Europe/Paris'; // fallback
            $parsedScheduledAt = DateParser::parse($scheduledAt->format(AbstractEsaboraService::FORMAT_DATE_TIME), $timezone);

            $intervention->setScheduledAt($parsedScheduledAt);
            ++$count;
            if (0 === $i % self::BATCH_SIZE) {
                $this->entityManager->flush();
            }
            ++$i;
            $progressBar->advance();
        }

        $this->entityManager->flush();
        $progressBar->finish();
        $io->newLine();
        $io->success("$count intervention(s) mise(s) à jour avec la bonne timezone.");

        return Command::SUCCESS;
    }
}
