<?php

namespace App\Tests\Functional\Service\Signalement\DesordreTraitement;

use App\Entity\DesordreCritere;
use App\Entity\DesordrePrecision;
use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Service\Signalement\DesordreTraitement\DesordreCompositionLogement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DesordreCompositionLogementTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testDefineDesordresLinkedToComposition()
    {
        $desordrePrecisionRepository = $this->entityManager->getRepository(DesordrePrecision::class);
        $desordreCritereRepository = $this->entityManager->getRepository(DesordreCritere::class);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = self::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-27']);
        $nbDesordrePrecision = $signalement->getDesordrePrecisions()->count();

        $desordreCompositionLogement = new DesordreCompositionLogement(
            $desordrePrecisionRepository,
            $desordreCritereRepository,
            $signalement);

        $typeCompositionLogement = $signalement->getTypeCompositionLogement();
        $typeCompositionLogement->setTypeLogementSousCombleSansFenetre('oui');

        $desordreCompositionLogement->defineDesordresLinkedToComposition($typeCompositionLogement);
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

        $desordreCompositionLogement->defineDesordresLinkedToComposition($typeCompositionLogement);
        $this->entityManager->persist($signalement);
        $this->entityManager->flush();
        $this->assertCount($nbDesordrePrecision, $signalement->getDesordrePrecisions());
        $this->assertFalse($signalement->hasDesordrePrecision($precisionToLink));
        $this->assertFalse($signalement->hasDesordreCritere($precisionToLink->getDesordreCritere()));
        $this->assertFalse($signalement->hasDesordreCategorie(
            $precisionToLink->getDesordreCritere()->getDesordreCategorie()));
    }
}
