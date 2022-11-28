<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Manager\AffectationManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Security;

class AffectationManagerTest extends KernelTestCase
{
    private ManagerRegistry $managerRegistry;
    private Security $security;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->managerRegistry = self::getContainer()->get(ManagerRegistry::class);
        $this->security = self::getContainer()->get('security.helper');
    }

    public function testRemoveAllPartnersFromAffectation(): void
    {
        $affectationManager = new AffectationManager($this->security, $this->managerRegistry, Affectation::class);

        /** @var Signalement $signalement */
        $signalement = $this->managerRegistry->getRepository(Signalement::class)->findOneBy(['reference' => '2022-8']);

        $countAffectationBeforeRemove = $signalement->getAffectations()->count();
        $affectationManager->removeAffectationsBy($signalement);
        $countAffectationAfterRemove = $signalement->getAffectations()->count();

        $this->assertNotEquals($countAffectationBeforeRemove, $countAffectationAfterRemove);
        $this->assertEquals(0, $countAffectationAfterRemove);
    }

    public function testRemoveSomePartnersFromAffectation(): void
    {
        $affectationManager = new AffectationManager($this->security, $this->managerRegistry, Affectation::class);

        /** @var Signalement $signalement */
        $signalement = $this->managerRegistry->getRepository(Signalement::class)->findOneBy(['reference' => '2022-8']);

        $partnersIdToRemove[] = $signalement->getAffectations()->get(0)->getPartner()->getId();
        $partnersIdToRemove[] = $signalement->getAffectations()->get(1)->getPartner()->getId();
        $countAffectationBeforeRemove = $signalement->getAffectations()->count();
        $affectationManager->removeAffectationsBy(signalement: $signalement, partnersIdToRemove: $partnersIdToRemove);
        $countAffectationAfterRemove = $signalement->getAffectations()->count();
        $this->assertNotEquals($countAffectationBeforeRemove, $countAffectationAfterRemove);
        $this->assertEquals(1, $countAffectationAfterRemove);
    }
}
