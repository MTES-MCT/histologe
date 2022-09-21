<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Repository\PartnerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PartnerRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testFindPartners(): void
    {
        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partners = $partnerRepository->findAllOrByInseeIfCommune(null, null);
        $this->assertTrue(\count($partners) > 0);
        $this->assertContainsOnlyInstancesOf(Partner::class, $partners);
    }

    public function testFindPartnersWithTerritory(): void
    {
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => '01']);

        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partners = $partnerRepository->findAllOrByInseeIfCommune(null, $territory);

        $this->assertTrue(\count($partners) > 0);
        $this->assertContainsOnlyInstancesOf(Partner::class, $partners);
        /** @var Partner $partner */
        foreach ($partners as $partner) {
            $this->assertInstanceOf(Territory::class, $partner->getTerritory());
        }
    }

    public function testFindPartnersByIntInseeWithTerritory(): void
    {
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => '13']);

        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partners = $partnerRepository->findAllOrByInseeIfCommune(13215, $territory);

        /** @var Partner $partner */
        foreach ($partners as $partner) {
            $this->assertContains('13215', $partner->getInsee());
        }
    }

    public function testFindPartnersByStringInseeWithTerritory(): void
    {
        $territoryRepository = $this->entityManager->getRepository(Territory::class);
        $territory = $territoryRepository->findOneBy(['zip' => '2A']);

        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->entityManager->getRepository(Partner::class);
        $partners = $partnerRepository->findAllOrByInseeIfCommune('2A247', $territory);

        /** @var Partner $partner */
        foreach ($partners as $partner) {
            $this->assertContains('2A247', $partner->getInsee());
        }
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();
    }
}
