<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\Criticite;
use App\Entity\Enum\Qualification;
use App\Entity\Signalement;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class QualificationServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    protected ManagerRegistry $managerRegistry;
    private SignalementQualificationUpdater $signalementQualificationUpdater;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        $container = static::getContainer();
        $this->signalementQualificationUpdater = $container->get(SignalementQualificationUpdater::class);
    }

    /**
     * @dataProvider provideScoreAndCriticite
     */
    public function testInitQualification(int $score, array $listCriticites, array $qualificationsToCheck): void
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $criticiteRepository = $this->entityManager->getRepository(Criticite::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-1']);
        $signalement->setNewScoreCreation($score);
        foreach ($listCriticites as $criticite) {
            $signalement->addCriticite($criticiteRepository->findOneBy(['label' => $criticite]));
        }

        $this->signalementQualificationUpdater->updateQualificationFromScore($signalement);
        $signalementQualifications = $signalement->getSignalementQualifications();

        $this->assertEquals(\count($qualificationsToCheck), \count($signalementQualifications));
    }

    private function provideScoreAndCriticite(): \Generator
    {
        yield 'RSD, NON_DECENCE' => [5, [], [Qualification::RSD, Qualification::NON_DECENCE]];

        yield 'RSD, NON_DECENCE, INSALUBRITE by score' => [11, [], [Qualification::RSD, Qualification::NON_DECENCE, Qualification::INSALUBRITE]];

        $listLabelCriticites = [
            'Pièce unique du logement de moins de 9m2',
        ];
        yield 'RSD, NON_DECENCE, INSALUBRITE by criticite' => [5, $listLabelCriticites, [Qualification::RSD, Qualification::NON_DECENCE, Qualification::INSALUBRITE]];

        $listLabelCriticites = [
            'Hauteur sous plafond du logement de moins de 2.20m',
        ];
        yield 'RSD, NON_DECENCE, DANGER' => [5, $listLabelCriticites, [Qualification::RSD, Qualification::NON_DECENCE, Qualification::DANGER]];

        $listLabelCriticites = [
            'Hauteur sous plafond du logement de moins de 2.20m',
        ];
        yield 'RSD, NON_DECENCE, DANGER, INSALUBRITE by score' => [11, $listLabelCriticites, [Qualification::RSD, Qualification::NON_DECENCE, Qualification::DANGER, Qualification::INSALUBRITE]];

        $listLabelCriticites = [
            'Hauteur sous plafond du logement de moins de 2.20m',
            'Pièce unique du logement de moins de 9m2',
        ];
        yield 'RSD, NON_DECENCE, DANGER, INSALUBRITE by criticite' => [5, $listLabelCriticites, [Qualification::RSD, Qualification::NON_DECENCE, Qualification::DANGER, Qualification::INSALUBRITE]];
    }
}
