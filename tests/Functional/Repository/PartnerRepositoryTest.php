<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
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
        $this->assertCount(5, $partners);
    }

    public function testFindPossiblePartnersForCOR69(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-2']);

        $partners = $this->partnerRepository->findByLocalization($signalement, false);
        $this->assertCount(3, $partners);

        $partnerCOR = array_filter($partners, function ($partner) {
            return 'COR' === $partner['name'];
        });
        $this->assertCount(1, $partnerCOR);
    }

    public function testFindPossiblePartnersForMDL69(): void
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);

        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-3']);

        $partners = $this->partnerRepository->findByLocalization($signalement, false);
        $this->assertCount(2, $partners);

        $partnerMDL = array_filter($partners, function ($partner) {
            return 'EMHA - Métropole de Lyon' === $partner['name'];
        });
        $this->assertCount(1, $partnerMDL);
    }

    public function testGetPartnerPaginator(): void
    {
        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => '69']);
        $partnerPaginator = $this->partnerRepository->getPartners($territory, null, null, 1);

        $this->assertGreaterThan(1, $partnerPaginator->count());
    }

    public function testGetPartnerQueryBuilder(): void
    {
        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => '69']);
        /** @var QueryBuilder $partnersQueryBuilder */
        $partnersQueryBuilder = $this->partnerRepository->getPartnersQueryBuilder($territory);
        $partners = $partnersQueryBuilder->getQuery()->getResult();
        /** @var Partner $partner */
        foreach ($partners as $partner) {
            $this->assertEquals('69', $partner->getTerritory()->getZip());
        }

        $this->assertGreaterThan(1, $partnersQueryBuilder->getQuery()->getResult());
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();
    }
}
