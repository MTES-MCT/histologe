<?php

namespace App\Command;

use App\Entity\Enum\DocumentType;
use App\Factory\FileFactory;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:migrate-territory-grid-files',
    description: 'Migrate territory grid files',
)]
class MigrateTerritoryGridFilesCommand extends Command
{
    public function __construct(
        private readonly TerritoryRepository $territoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly FileFactory $fileFactory,
        private readonly UserRepository $userRepository,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->parameterBag->get('feature_new_document_space')) {
            $io->error('Cette commande n\'est pas executable car la fonctionnalité de nouvel espace document n\'est pas activée');

            return Command::SUCCESS;
        }

        $userAdmin = $this->userRepository->findOneBy(['email' => $this->parameterBag->get('admin_email')]);

        $territoriesWithGridFiles = $this->territoryRepository->findAllWithGridFile();
        foreach ($territoriesWithGridFiles as $territory) {
            $gridFileName = $territory->getGrilleVisiteFilename();

            $file = $this->fileFactory->createInstanceFrom(
                filename: $gridFileName,
                title: 'Grille de visite - '.$territory->getZip(),
                description: 'Grille de repérage et d\'évaluation des désordres d\'un logement, pouvant relever de l\'habitat indigne.',
                user: $userAdmin,
                isStandalone: true,
                documentType: DocumentType::GRILLE_DE_VISITE,
                territory: $territory,
            );

            $this->entityManager->persist($file);

            $io->info('File '.$gridFileName.' for territory zip '.$territory->getZip().' has been migrated with success');
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
