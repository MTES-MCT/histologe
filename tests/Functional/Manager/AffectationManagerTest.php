<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Manager\AffectationManager;
use App\Manager\SuiviManager;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AffectationManagerTest extends KernelTestCase
{
    private const REF_SIGNALEMENT = '2022-8';
    private ManagerRegistry $managerRegistry;
    private SuiviManager $suiviManager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->managerRegistry = self::getContainer()->get(ManagerRegistry::class);
        $this->suiviManager = self::getContainer()->get(SuiviManager::class);
        $this->logger = self::getContainer()->get(LoggerInterface::class);
    }

    public function testRemoveAllPartnersFromAffectation(): void
    {
        $affectationManager = new AffectationManager(
            $this->managerRegistry,
            $this->suiviManager,
            $this->logger,
            Affectation::class,
        );

        /** @var Signalement $signalement */
        $signalement = $this->managerRegistry->getRepository(Signalement::class)->findOneBy(
            ['reference' => self::REF_SIGNALEMENT]
        );

        $countAffectationBeforeRemove = $signalement->getAffectations()->count();
        $affectationManager->removeAffectationsFrom($signalement);
        $countAffectationAfterRemove = $signalement->getAffectations()->count();

        $this->assertNotEquals($countAffectationBeforeRemove, $countAffectationAfterRemove);
        $this->assertEquals(0, $countAffectationAfterRemove);
    }

    public function testRemoveSomePartnersFromAffectation(): void
    {
        $affectationManager = new AffectationManager(
            $this->managerRegistry,
            $this->suiviManager,
            $this->logger,
            Affectation::class,
        );

        /** @var Signalement $signalement */
        $signalement = $this->managerRegistry->getRepository(Signalement::class)->findOneBy(
            ['reference' => self::REF_SIGNALEMENT]
        );

        $partnersIdToRemove[] = $signalement->getAffectations()->get(0)->getPartner()->getId();
        $partnersIdToRemove[] = $signalement->getAffectations()->get(1)->getPartner()->getId();
        $countAffectationBeforeRemove = $signalement->getAffectations()->count();
        $affectationManager->removeAffectationsFrom(
            signalement: $signalement,
            postedPartner: [],
            partnersIdToRemove: $partnersIdToRemove
        );
        $countAffectationAfterRemove = $signalement->getAffectations()->count();
        $this->assertNotEquals($countAffectationBeforeRemove, $countAffectationAfterRemove);
        $this->assertEquals(1, $countAffectationAfterRemove);
    }
}
