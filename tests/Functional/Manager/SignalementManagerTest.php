<?php

namespace App\Tests\Functional\Manager;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Manager\SignalementManager;
use App\Repository\PartnerRepository;
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
        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        /** @var Security $security */
        $security = static::getContainer()->get('security.helper');

        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->find(self::TERRITORY_13);

        $signalementManager = new SignalementManager($managerRegistry, $partnerRepository, $security);
        $signalement = $signalementManager->findOneBy(['territory' => self::TERRITORY_13]);

        $partners = $signalementManager->findAllPartners($signalement);

        $this->assertArrayHasKey('affected', $partners);
        $this->assertArrayHasKey('not_affected', $partners);

        $this->assertCount(1, $partners['affected'], 'One partner should be affected');
        $this->assertCount(3, $partners['not_affected'], 'Three partners should not be affected');
    }
}
