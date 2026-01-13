<?php

namespace App\Command\Temp;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-injonction-status',
    description: 'Fix signalements that changed status from INJONCTION_BAILLEUR by error',
)]
class CancelInjonctionToPnlhiCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'test',
            't',
            InputOption::VALUE_NONE,
            'Test mode - does not apply changes'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $testMode = $input->getOption('test');

        if ($testMode) {
            $io->info('Mode TEST activé - aucune modification ne sera appliquée');
        }

        // Trouver les signalements concernés
        $qb = $this->em->createQueryBuilder();
        $qb->select('s')
            ->from(Signalement::class, 's')
            ->leftJoin('s.suivis', 'suiviReponse')
            ->leftJoin('s.suivis', 'suiviExpire')
            ->where('s.referenceInjonction IS NOT NULL')
            ->andWhere('s.statut != :statut')
            ->andWhere('suiviReponse.category IN (:categoriesReponse)')
            ->andWhere('suiviExpire.category = :categorieExpiree')
            ->setParameter('statut', SignalementStatus::INJONCTION_BAILLEUR)
            ->setParameter('categoriesReponse', [
                SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI,
                SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE,
            ])
            ->setParameter('categorieExpiree', SuiviCategory::INJONCTION_BAILLEUR_EXPIREE)
            ->groupBy('s.id');

        $signalements = $qb->getQuery()->getResult();

        if (empty($signalements)) {
            $io->success('Aucun signalement trouvé.');

            return Command::SUCCESS;
        }

        // Afficher la liste des signalements
        $io->section(sprintf('Signalements trouvés : %d', count($signalements)));
        $rows = [];
        foreach ($signalements as $signalement) {
            $rows[] = [
                $signalement->getUuid(),
                $signalement->getReference(),
                $signalement->getTerritory()->getName(),
                $signalement->getStatut()->value,
            ];
        }
        $io->table(['UUID', 'Référence', 'Territoire', 'Statut actuel'], $rows);

        // Si mode test, on s'arrête là
        if ($testMode) {
            return Command::SUCCESS;
        }

        // Demander confirmation
        if (!$io->confirm('Voulez-vous corriger ces signalements ?', false)) {
            $io->info('Opération annulée.');

            return Command::SUCCESS;
        }

        // Corriger les signalements
        $io->section('Correction en cours...');
        $io->progressStart(count($signalements));

        foreach ($signalements as $signalement) {
            // Repasser en statut INJONCTION_BAILLEUR
            $signalement->setStatut(SignalementStatus::INJONCTION_BAILLEUR);

            // Supprimer les suivis avec catégorie INJONCTION_BAILLEUR_EXPIREE
            foreach ($signalement->getSuivis() as $suivi) {
                if (SuiviCategory::INJONCTION_BAILLEUR_EXPIREE === $suivi->getCategory()) {
                    $this->em->remove($suivi);
                }
            }

            $io->progressAdvance();
        }

        $this->em->flush();
        $io->progressFinish();

        $io->success(sprintf('%d signalement(s) corrigé(s) avec succès.', count($signalements)));

        return Command::SUCCESS;
    }
}
