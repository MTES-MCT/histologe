<?php

namespace App\Command;

use App\Entity\Enum\DocumentType;
use App\Repository\FileRepository;
use App\Repository\TerritoryRepository;
use App\Service\UploadHandlerService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-duplicated-visit-grids',
    description: 'Cleanup duplicated GRILLE_DE_VISITE files and keep only one per territory',
)]
class CleanupDuplicatedVisitGridsCommand extends Command
{
    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly TerritoryRepository $territoryRepository,
        private readonly UploadHandlerService $uploadHandlerService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute without deleting files (simulation mode)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');

        if ($isDryRun) {
            $io->warning('Mode simulation activé - aucun fichier ne sera supprimé');
        }

        $territories = $this->territoryRepository->findAll();
        $totalDeleted = 0;

        foreach ($territories as $territory) {
            $territoryZip = $territory->getZip();
            $io->section('Traitement du territoire: '.$territory->getName().' ('.$territoryZip.')');

            // Récupérer toutes les grilles de visite pour ce territoire
            $visitGrids = $this->fileRepository->findBy([
                'territory' => $territory,
                'documentType' => DocumentType::GRILLE_DE_VISITE,
                'isStandalone' => true,
            ]);

            if (empty($visitGrids)) {
                $io->info('Aucune grille de visite trouvée pour ce territoire');
                continue;
            }

            $io->info(sprintf('Trouvé %d grille(s) de visite', count($visitGrids)));

            // Identifier la grille à conserver (celle dont le titre commence par "Grille de visite - {zip}")
            $targetPrefix = 'Grille de visite - '.$territoryZip;
            $filesToDelete = [];

            foreach ($visitGrids as $file) {
                if (!str_starts_with($file->getTitle(), $targetPrefix)) {
                    $filesToDelete[] = $file;
                }
            }

            if (!empty($filesToDelete)) {
                $io->info(sprintf('%d fichier(s) à supprimer:', count($filesToDelete)));

                foreach ($filesToDelete as $file) {
                    $io->text(sprintf('  - "%s" (ID: %d, Fichier: %s)', $file->getTitle(), $file->getId(), $file->getFilename()));

                    if (!$isDryRun) {
                        $deleted = $this->uploadHandlerService->deleteFile($file);
                        if ($deleted) {
                            $io->text('    ✓ Supprimé');
                            ++$totalDeleted;
                        } else {
                            $io->error('    ✗ Erreur lors de la suppression');
                        }
                    }
                }

                if ($isDryRun) {
                    $io->text('  [Mode simulation - aucune suppression effectuée]');
                }
            } else {
                $io->info('Aucun fichier à supprimer pour ce territoire');
            }

            $io->newLine();
        }

        $io->success(sprintf(
            'Terminé ! %d fichier(s) %s',
            $totalDeleted,
            $isDryRun ? 'à supprimer' : 'supprimé(s)'
        ));

        return Command::SUCCESS;
    }
}
