<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Signalement;
use App\Factory\FileFactory;
use App\Manager\FileManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FileManagerTest extends KernelTestCase
{
    public function testCreateFile(): void
    {
        $fileFactory = static::getContainer()->get(FileFactory::class);
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        $fileManager = new FileManager($fileFactory, $managerRegistry);

        $signalementRepository = $managerRegistry->getRepository(Signalement::class);

        $file = $fileManager->createOrUpdate(
            'blank.pdf',
            'Blank',
            File::FILE_TYPE_DOCUMENT,
            $signalement = $signalementRepository->findOneBy(['reference' => '2023-12'])
        );

        $this->assertEquals('blank.pdf', $file->getFilename());
        $this->assertEquals('Blank', $file->getTitle());
        $this->assertEquals('document', $file->getFileType());
        $this->assertEquals($signalement->getReference(), $file->getSignalement()->getReference());
        $this->assertEquals(DocumentType::AUTRE, $file->getDocumentType());
    }

    public function testCreatePhotoVisite(): void
    {
        $fileFactory = static::getContainer()->get(FileFactory::class);
        /** @var ManagerRegistry $managerRegistry */
        $managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        $fileManager = new FileManager($fileFactory, $managerRegistry);

        $signalementRepository = $managerRegistry->getRepository(Signalement::class);

        $desc = 'une photo de la cave plein de moisissure';
        $file = $fileManager->createOrUpdate(
            filename: 'blank.jpg',
            title: 'Blank',
            type: File::FILE_TYPE_PHOTO,
            signalement: $signalement = $signalementRepository->findOneBy(['reference' => '2023-12']),
            description: $desc,
            documentType: DocumentType::PROCEDURE_RAPPORT_DE_VISITE
        );

        $this->assertEquals('blank.jpg', $file->getFilename());
        $this->assertEquals('Blank', $file->getTitle());
        $this->assertEquals('photo', $file->getFileType());
        $this->assertEquals($signalement->getReference(), $file->getSignalement()->getReference());
        $this->assertEquals($desc, $file->getDescription());
        $this->assertEquals(DocumentType::PROCEDURE_RAPPORT_DE_VISITE, $file->getDocumentType());
    }
}
