<?php

namespace App\Tests\Functional\Service\Signalement\DesordreTraitement;

use App\Entity\DesordrePrecision;
use App\Entity\Signalement;
use App\Repository\DesordrePrecisionRepository;
use App\Repository\SignalementRepository;
use App\Service\Signalement\DesordreTraitement\DesordreCompositionLogementLoader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DesordreCompositionLogementLoaderTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
    }

    public function testDefineDesordresLinkedToComposition(): void
    {
        /** @var DesordrePrecisionRepository $desordrePrecisionRepository */
        $desordrePrecisionRepository = $this->entityManager->getRepository(DesordrePrecision::class);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-27']);
        $nbDesordrePrecision = $signalement->getDesordrePrecisions()->count();

        $desordreCompositionLogement = new DesordreCompositionLogementLoader(
            $desordrePrecisionRepository,
        );

        $typeCompositionLogement = $signalement->getTypeCompositionLogement();
        $typeCompositionLogement->setTypeLogementSousCombleSansFenetre('oui');

        $desordreCompositionLogement->load($signalement, $typeCompositionLogement);
        $this->entityManager->persist($signalement);
        $this->entityManager->flush();

        /** @var DesordrePrecision $precisionToLink */
        $precisionToLink = $desordrePrecisionRepository->findOneBy(
            ['desordrePrecisionSlug' => 'desordres_type_composition_logement_sous_combles']
        );

        $this->assertCount($nbDesordrePrecision + 1, $signalement->getDesordrePrecisions());
        $this->assertTrue($signalement->hasDesordrePrecision($precisionToLink));

        $typeCompositionLogement->setTypeLogementSousCombleSansFenetre('non');

        $desordreCompositionLogement->load($signalement, $typeCompositionLogement);
        $this->entityManager->persist($signalement);
        $this->entityManager->flush();
        $this->assertCount($nbDesordrePrecision, $signalement->getDesordrePrecisions());
        $this->assertFalse($signalement->hasDesordrePrecision($precisionToLink));
    }
}
