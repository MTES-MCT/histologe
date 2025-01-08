<?php

namespace App\Tests\Functional\Service\Signalement\DesordreTraitement;

use App\Entity\DesordreCritere;
use App\Entity\DesordrePrecision;
use App\Entity\Signalement;
use App\Repository\DesordreCritereRepository;
use App\Repository\DesordrePrecisionRepository;
use App\Repository\SignalementRepository;
use App\Service\Signalement\DesordreTraitement\DesordreCompositionLogementLoader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DesordreCompositionLogementLoaderTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testDefineDesordresLinkedToComposition()
    {
        /** @var DesordrePrecisionRepository $desordrePrecisionRepository */
        $desordrePrecisionRepository = $this->entityManager->getRepository(DesordrePrecision::class);
        /** @var DesordreCritereRepository $desordreCritereRepository */
        $desordreCritereRepository = $this->entityManager->getRepository(DesordreCritere::class);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-27']);
        $nbDesordrePrecision = $signalement->getDesordrePrecisions()->count();

        $desordreCompositionLogement = new DesordreCompositionLogementLoader(
            $desordrePrecisionRepository,
            $desordreCritereRepository,
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
        $this->assertTrue($signalement->hasDesordreCritere($precisionToLink->getDesordreCritere()));
        $this->assertTrue(
            $signalement->hasDesordreCategorie(
                $precisionToLink->getDesordreCritere()->getDesordreCategorie()
            )
        );

        $typeCompositionLogement->setTypeLogementSousCombleSansFenetre('non');

        $desordreCompositionLogement->load($signalement, $typeCompositionLogement);
        $this->entityManager->persist($signalement);
        $this->entityManager->flush();
        $this->assertCount($nbDesordrePrecision, $signalement->getDesordrePrecisions());
        $this->assertFalse($signalement->hasDesordrePrecision($precisionToLink));
        $this->assertFalse($signalement->hasDesordreCritere($precisionToLink->getDesordreCritere()));
        $this->assertFalse($signalement->hasDesordreCategorie(
            $precisionToLink->getDesordreCritere()->getDesordreCategorie()
        ));
    }
}
