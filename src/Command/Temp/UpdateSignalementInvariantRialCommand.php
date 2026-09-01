<?php

namespace App\Command\Temp;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Service\Gouv\Rial\RialService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-signalement-invariant-rial',
    description: 'Mise à jour de l\'invariant fiscal rial numero_invariant_rial à l\'aide de RialService',
)]
class UpdateSignalementInvariantRialCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SignalementRepository $signalementRepository,
        private readonly RialService $rialService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dateLimit = '2026-08-07 13:37:03';

        $queryBuilder = $this->signalementRepository->createQueryBuilder('s');
        $queryBuilder
            ->where('s.createdAt > :dateLimit')
            ->andWhere('s.numeroInvariantRial IS NULL')
            ->setParameter('dateLimit', $dateLimit);

        /** @var Signalement[] $signalements */
        $signalements = $queryBuilder->getQuery()->getResult();

        $io->info(sprintf('%d signalements à traiter.', count($signalements)));

        $countUpdated = 0;
        $updatedRows = [];
        $io->progressStart(count($signalements));
        foreach ($signalements as $signalement) {
            $banId = $signalement->getAddress()->getBanId();
            if (empty($banId)) {
                $io->progressAdvance();
                continue;
            }

            $invariantRial = $this->rialService->getSingleInvariantByBanId($banId);
            if (!empty($invariantRial)) {
                $signalement->setNumeroInvariantRial($invariantRial);
                $updatedRows[] = [
                    $signalement->getId(),
                    $banId,
                    $invariantRial,
                ];
                ++$countUpdated;
            }
            $io->progressAdvance();
        }
        $io->progressFinish();

        $this->entityManager->flush();

        if (!empty($updatedRows)) {
            $io->section('Tableau récapitulatif des invariants mis à jour');
            $io->table(
                ['id', 'ban_id', 'invariant_fiscal_rial'],
                $updatedRows
            );
        }

        $io->success(sprintf('%d signalements ont été mis à jour.', $countUpdated));

        return Command::SUCCESS;
    }
}
