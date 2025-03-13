<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\DocumentType;
use App\Factory\FileFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadFileData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private readonly FileFactory $fileFactory,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $file = $this->fileFactory->createInstanceFrom(
            filename: 'export-histologe-xxxx.xslx',
            title: 'export-histologe-xxxx.xslx',
            documentType: DocumentType::EXPORT,
        );
        $file->setCreatedAt(new \DateTimeImmutable('- 2 months'));
        $manager->persist($file);
        $manager->flush();
    }

    public function getOrder(): int
    {
        return 22;
    }
}
