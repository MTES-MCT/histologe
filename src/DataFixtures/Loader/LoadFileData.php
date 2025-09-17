<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\DocumentType;
use App\Entity\Enum\Qualification;
use App\Entity\File;
use App\Factory\FileFactory;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LoadFileData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly FileFactory $fileFactory,
        private readonly UserRepository $userRepository,
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

        $userAdmin = $this->userRepository->findOneBy(['email' => $this->parameterBag->get('admin_email')]);
        foreach (File::STANDALONE_FILES as $title => $filename) {
            $file = $this->fileFactory->createInstanceFrom(
                filename: $filename,
                title: $title,
                user: $userAdmin,
                isStandalone: true,
                documentType: DocumentType::MODELE_DE_COURRIER,
                partnerCompetence: [Qualification::NON_DECENCE_ENERGETIQUE],
                description: 'Modèle de courrier pour '.$title,
            );
            $manager->persist($file);
        }

        $manager->flush();
    }

    public function getOrder(): int
    {
        return 22;
    }
}
