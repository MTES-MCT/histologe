<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Manager\AffectationManager;
use App\Manager\SuiviManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AffectationManagerTest extends KernelTestCase
{
    private ManagerRegistry $managerRegistry;
    private SuiviManager $suiviManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->managerRegistry = self::getContainer()->get(ManagerRegistry::class);
        $this->suiviManager = self::getContainer()->get(SuiviManager::class);
    }

    public function testRemoveAllPartnersFromAffectation(): void
    {
        $affectationManager = new AffectationManager($this->managerRegistry, $this->suiviManager, Affectation::class);

        /** @var Signalement $signalement */
        $signalement = $this->managerRegistry->getRepository(Signalement::class)->findOneBy(['reference' => '2022-8']);

        $countAffectationBeforeRemove = $signalement->getAffectations()->count();
        $affectationManager->removeAffectationsFrom($signalement);
        $countAffectationAfterRemove = $signalement->getAffectations()->count();

        $this->assertNotEquals($countAffectationBeforeRemove, $countAffectationAfterRemove);
        $this->assertEquals(0, $countAffectationAfterRemove);
    }

    public function testRemoveSomePartnersFromAffectation(): void
    {
        $affectationManager = new AffectationManager($this->managerRegistry, $this->suiviManager, Affectation::class);

        /** @var Signalement $signalement */
        $signalement = $this->managerRegistry->getRepository(Signalement::class)->findOneBy(['reference' => '2022-8']);

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
