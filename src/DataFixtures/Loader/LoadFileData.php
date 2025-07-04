<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\DocumentType;
use App\Factory\FileFactory;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LoadFileData extends Fixture implements OrderedFixtureInterface
{
    public const STANDALONE_FILES = [
        '1 - Demande de transmission d\'une copie d\'un DPE' => '1_Demande_de_transmission_d_une_copie_d_un_DPE.docx',
        '2 - Information au bailleur - Mise en conformité' => '2_Information_au_bailleur_Mise_en_conformite.docx',
        '3 - Mise en demeure' => '3_Mise_en_demeure.docx',
        '4 - Invitation à contacter l\'ADIL' => '4_Invitation_a_contacter_l_ADIL.docx',
        '5 - Engagement du bailleur à réaliser des travaux' => '5_Engagement_du_bailleur_a_realiser_des_travaux.docx',
        '6 - Saisine de la Commission départementale de conciliation' => '6_Saisine_de_la_Commission_departementale_de_conciliation.docx',
    ];

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
        foreach (self::STANDALONE_FILES as $title => $filename) {
            $file = $this->fileFactory->createInstanceFrom(
                filename: $filename,
                title: $title,
                user: $userAdmin,
                isStandalone: true
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
