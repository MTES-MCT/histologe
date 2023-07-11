<?php

namespace App\Manager;

use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\User;
use App\Factory\FileFactory;
use App\Repository\FileRepository;
use Doctrine\Persistence\ManagerRegistry;

class FileManager extends AbstractManager
{
    public function __construct(
        private readonly FileFactory $fileFactory,
        ManagerRegistry $managerRegistry,
        string $entityName = File::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createOrUpdate(
        string $filename = null,
        string $title = null,
        string $type = null,
        ?Signalement $signalement = null,
        ?User $user = null,
        bool $flush = false
    ): File {
        /** @var FileRepository $fileRepository */
        $fileRepository = $this->getRepository();
        $file = $fileRepository->findOneBy(['filename' => $filename]);
        if (null === $file) {
            $file = $this->fileFactory->createInstanceFrom($filename, $title, $type, $signalement, $user);
        }

        $file
            ->setTitle($title)
            ->setFileType($type)
            ->setSignalement($signalement);

        $this->save($file, $flush);

        return $file;
    }
}
