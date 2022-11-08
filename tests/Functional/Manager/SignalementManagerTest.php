<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Affectation;
use App\Entity\Enum\MotifCloture;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Manager\SignalementManager;
use App\Repository\TerritoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Security;

class SignalementManagerTest extends KernelTestCase
{
    public const TERRITORY_13 = 13;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testFindAllPartnersAffectedAndNotAffectedBySignalementLocalization()
    {
        $managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        /** @var Security $security */
        $security = static::getContainer()->get('security.helper');

        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->find(self::TERRITORY_13);

        $signalementManager = new SignalementManager($managerRegistry, $security);
        $signalement = $signalementManager->findOneBy(['territory' => self::TERRITORY_13]);

        $partners = $signalementManager->findAllPartners($signalement);

        $this->assertArrayHasKey('affected', $partners);
        $this->assertArrayHasKey('not_affected', $partners);

        $this->assertCount(1, $partners['affected'], 'One partner should be affected');
        $this->assertCount(3, $partners['not_affected'], 'Three partners should not be affected');
    }

    public function testCloseSignalementForAllPartners()
    {
        $managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalementActive = $signalementRepository->findOneBy(['statut' => Signalement::STATUS_ACTIVE]);

        /** @var Security $security */
        $security = static::getContainer()->get('security.helper');
        $signalementManager = new SignalementManager($managerRegistry, $security);
        $signalementClosed = $signalementManager->closeSignalementForAllPartners(
            $signalementActive,
            MotifCloture::LABEL['RESOLU']
        );

        $this->assertInstanceOf(Signalement::class, $signalementClosed);
        $this->assertEquals(Signalement::STATUS_CLOSED, $signalementClosed->getStatut());
        $this->assertEquals('Problème résolu', $signalementClosed->getMotifCloture());
        $this->assertInstanceOf(\DateTimeInterface::class, $signalementClosed->getClosedAt());

        $signalementHasAllAffectationsClosed = $signalementClosed->getAffectations()
            ->forAll(function (int $index, Affectation $affectation) {
                return Affectation::STATUS_CLOSED === $affectation->getStatut()
                && str_contains($affectation->getMotifCloture(), 'Problème résolu');
            });

        $this->assertTrue($signalementHasAllAffectationsClosed);
    }

    public function testCloseAffectation()
    {
        $managerRegistry = static::getContainer()->get(ManagerRegistry::class);
        $affectationRepository = $this->entityManager->getRepository(Affectation::class);
        $affectationAccepted = $affectationRepository->findOneBy(['statut' => Affectation::STATUS_ACCEPTED]);

        /** @var Security $security */
        $security = static::getContainer()->get('security.helper');
        $signalementManager = new SignalementManager($managerRegistry, $security);
        $affectationClosed = $signalementManager->closeAffectation(
            $affectationAccepted,
            MotifCloture::LABEL['NON_DECENCE']
        );

        $this->assertEquals(Affectation::STATUS_CLOSED, $affectationClosed->getStatut());
        $this->assertInstanceOf(\DateTimeInterface::class, $affectationClosed->getAnsweredAt());
        $this->assertTrue(str_contains($affectationClosed->getMotifCloture(), 'Non décence'));
    }
}
