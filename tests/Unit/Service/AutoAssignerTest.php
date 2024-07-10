<?php

namespace App\Tests\Unit\Service;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Factory\SuiviFactory;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Messenger\InterconnectionBus;
use App\Service\Signalement\AutoAssigner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AutoAssignerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private AffectationManager $affectationManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->affectationManager = self::getContainer()->get(AffectationManager::class);
    }

    public function testAutoAssignmentSuccess(): void
    {
        $this->testHelper('2024-05', 1);
    }

    public function testAutoAssignmentFailed(): void
    {
        $this->testHelper('2023-1', 0);
    }

    public function testAutoAssignmentHerault(): void
    {
        $this->testHelper('2024-06', 4);
    }

    private function testHelper($reference, $expectedCount)
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['reference' => $reference]);

        $signalementManager = $this->createMock(SignalementManager::class);
        $suiviManager = $this->createMock(SuiviManager::class);
        $suiviFactory = $this->createMock(SuiviFactory::class);
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $userManager = $this->createMock(UserManager::class);
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $esaboraBus = $this->createMock(InterconnectionBus::class);
        $autoAssigner = new AutoAssigner(
            $signalementManager,
            $this->affectationManager,
            $suiviManager,
            $suiviFactory,
            $partnerRepository,
            $userManager,
            $parameterBag,
            $esaboraBus,
        );

        $autoAssigner->assign($signalement);
        $this->assertEquals($expectedCount, $autoAssigner->getCountAffectations());
    }
}
