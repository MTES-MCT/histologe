<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PartnerRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private PartnerRepository $partnerRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->partnerRepository = $this->entityManager->getRepository(Partner::class);
    }

    public function testFindPartnersAffected(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2022-1']);

        $partners = $this->partnerRepository->findByLocalization($signalement, true);
        $this->assertCount(1, $partners);
    }

    public function testFindPartnersNotAffected(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2022-1']);

        $partners = $this->partnerRepository->findByLocalization($signalement, false);
        $this->assertCount(3, $partners);
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();
    }
}
