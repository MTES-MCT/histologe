<?php

namespace App\Command;

use App\Entity\Enum\ProcedureType;
use App\Entity\Intervention;
use App\Repository\InterventionRepository;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-sish-arrete-procedure',
    description: 'Update intervention with arrete procedure insalubrite',
)]
class UpdateSishArreteProcedureCommand extends Command
{
    public function __construct(
        private readonly InterventionRepository $interventionRepository,
        private readonly SignalementQualificationUpdater $qualificationUpdater,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arreteInterventions = $this->interventionRepository->getArreteToApplyProcedureInsalubrite();
        $countArreteInterventions = count($arreteInterventions);
        $progressBar = new ProgressBar($output, $countArreteInterventions);
        $progressBar->start();
        foreach ($arreteInterventions as $arreteIntervention) {
            /* @var Intervention $arreteIntervention */
            $arreteIntervention->setConcludeProcedure([ProcedureType::INSALUBRITE]);
            $signalement = $arreteIntervention->getSignalement();
            $this->qualificationUpdater->updateQualificationFromVisiteProcedureList(
                $signalement,
                [ProcedureType::INSALUBRITE]
            );
            $progressBar->advance();
        }
        $progressBar->finish();
        $io->success(sprintf('%s interventions de type arrếtés processed', $countArreteInterventions));

        return Command::SUCCESS;
    }
}
