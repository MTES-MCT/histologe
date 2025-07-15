<?php

namespace App\Command;

use App\Entity\File;
use App\Factory\FileFactory;
use App\Repository\UserRepository;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:save-standalone-files-in-file-entity',
    description: 'Save standalone files in File entity',
)]
class SaveStandaloneFilesInFileEntityCommand extends Command
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UploadHandlerService $uploadHandlerService,
        private readonly FileFactory $fileFactory,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userAdmin = $this->userRepository->findOneBy(['email' => $this->parameterBag->get('admin_email')]);

        foreach (File::STANDALONE_FILES as $title => $filename) {
            $this->uploadHandlerService->uploadFromFilename($filename, $this->parameterBag->get('file_dir'));
            $file = $this->fileFactory->createInstanceFrom(
                filename: $filename,
                title: $title,
                user: $userAdmin,
                isStandalone: true
            );
            $this->entityManager->persist($file);
        }

        $this->entityManager->flush();
        $io->success(count(File::STANDALONE_FILES).' standalone files have been saved in File entity');

        return Command::SUCCESS;
    }
}
