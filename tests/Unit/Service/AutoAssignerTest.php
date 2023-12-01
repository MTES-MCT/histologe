<?php

namespace App\Tests\Unit\Service;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Manager\AffectationManager;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Manager\UserManager;
use App\Messenger\EsaboraBus;
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
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-1']);

        $signalementManager = $this->createMock(SignalementManager::class);
        $suiviManager = $this->createMock(SuiviManager::class);
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $userManager = $this->createMock(UserManager::class);
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $esaboraBus = $this->createMock(EsaboraBus::class);
        $autoAssigner = new AutoAssigner(
            $signalementManager,
            $this->affectationManager,
            $suiviManager,
            $partnerRepository,
            $userManager,
            $parameterBag,
            $esaboraBus,
        );
        $affectations = $autoAssigner->assign($signalement);

        $this->assertEquals(\count($affectations), 1);
    }

    public function testAutoAssignmentFailed(): void
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-2']);

        $signalementManager = $this->createMock(SignalementManager::class);
        $suiviManager = $this->createMock(SuiviManager::class);
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $userManager = $this->createMock(UserManager::class);
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $esaboraBus = $this->createMock(EsaboraBus::class);
        $autoAssigner = new AutoAssigner(
            $signalementManager,
            $this->affectationManager,
            $suiviManager,
            $partnerRepository,
            $userManager,
            $parameterBag,
            $esaboraBus,
        );
        $affectations = $autoAssigner->assign($signalement);

        $this->assertEquals(\count($affectations), 0);
    }
}
