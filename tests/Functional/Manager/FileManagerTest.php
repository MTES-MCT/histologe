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
        $this->assertEquals('pdf', $file->getExtension());
        $this->assertTrue($file->isTypeDocument());
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
            signalement: $signalement = $signalementRepository->findOneBy(['reference' => '2023-12']),
            description: $desc,
            documentType: DocumentType::PHOTO_VISITE
        );

        $this->assertEquals('blank.jpg', $file->getFilename());
        $this->assertEquals('Blank', $file->getTitle());
        $this->assertEquals('jpg', $file->getExtension());
        $this->assertTrue($file->isTypePhoto());
        $this->assertEquals($signalement->getReference(), $file->getSignalement()->getReference());
        $this->assertEquals($desc, $file->getDescription());
        $this->assertEquals(DocumentType::PHOTO_VISITE, $file->getDocumentType());
    }
}
