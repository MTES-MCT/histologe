<?php

namespace App\Command;

use App\Manager\AddressManager;
use App\Repository\SignalementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:copy-signalement-to-address',
    description: 'Copie les adresses des signalements dans la table address',
)]
class CopySignalementToAddressCommand extends Command
{
    private const BATCH_SIZE = 1000;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AddressManager $addressManager,
        private readonly SignalementRepository $signalementRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Copie des adresses des signalements vers la table Address');

        // Compter le nombre total de signalements à traiter
        $totalSignalements = (int) $this->signalementRepository->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.adresseOccupant IS NOT NULL')
            ->andWhere('s.villeOccupant IS NOT NULL')
            ->andWhere('s.cpOccupant IS NOT NULL')
            ->andWhere('s.inseeOccupant IS NOT NULL')
            ->andWhere('s.territory IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        // Créer une barre de progression
        $progressBar = new ProgressBar($output, $totalSignalements);
        $progressBar->setFormat('very_verbose');
        $progressBar->start();

        $processedCount = 0;
        $errorCount = 0;

        // Récupérer les signalements par batch pour éviter de charger tout en mémoire
        $offset = 0;

        while (true) {
            $queryBuilder = $this->signalementRepository->createQueryBuilder('s')
                ->where('s.adresseOccupant IS NOT NULL')
                ->andWhere('s.villeOccupant IS NOT NULL')
                ->andWhere('s.cpOccupant IS NOT NULL')
                ->andWhere('s.inseeOccupant IS NOT NULL')
                ->andWhere('s.territory IS NOT NULL')
                ->setFirstResult($offset)
                ->setMaxResults(self::BATCH_SIZE);

            $signalements = $queryBuilder->getQuery()->getResult();

            if (empty($signalements)) {
                break;
            }

            foreach ($signalements as $signalement) {
                try {
                    $this->addressManager->createOrUpdateFrom($signalement);
                    // Flush individuels pour éviter les erreurs de doublons
                    $this->entityManager->flush();

                    ++$processedCount;
                } catch (\Exception $e) {
                    ++$errorCount;
                    if ($output->isVerbose()) {
                        $io->error(sprintf(
                            'Erreur lors du traitement du signalement %s : %s',
                            $signalement->getReference(),
                            $e->getMessage()
                        ));
                    }
                }

                $progressBar->advance();
            }

            $offset += self::BATCH_SIZE;
        }

        $progressBar->finish();
        $io->newLine(2);

        // Afficher le résumé
        $io->success('Copie terminée avec succès !');
        $io->table(
            ['Statistiques', 'Nombre'],
            [
                ['Total traité', $processedCount],
                ['Erreurs', $errorCount],
            ]
        );

        return Command::SUCCESS;
    }
}
