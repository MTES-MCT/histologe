<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Repository\AffectationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AffectationRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
    }

    public function testFindAffectationSubscribedToEsabora(): void
    {
        /** @var AffectationRepository $affectationRepository */
        $affectationRepository = $this->entityManager->getRepository(Affectation::class);
        $affectationsSubscribedToEsabora = $affectationRepository->findAffectationSubscribedToEsabora(PartnerType::ARS);
        $this->assertCount(5, $affectationsSubscribedToEsabora);
        foreach ($affectationsSubscribedToEsabora as $row) {
            $affectationSubscribedToEsabora = $row['affectation'];
            $this->assertCount(2, $affectationSubscribedToEsabora->getPartner()->getEsaboraCredential());
        }

        $affectationsSubscribedToEsabora = $affectationRepository->findAffectationSubscribedToEsabora(PartnerType::COMMUNE_SCHS);
        $this->assertCount(1, $affectationsSubscribedToEsabora);
        foreach ($affectationsSubscribedToEsabora as $row) {
            $affectationSubscribedToEsabora = $row['affectation'];
            $this->assertCount(2, $affectationSubscribedToEsabora->getPartner()->getEsaboraCredential());
        }

        $affectationsSubscribedToEsabora = $affectationRepository->findAffectationSubscribedToEsabora(
            PartnerType::ARS,
            true,
            '00000000-0000-0000-2023-000000000012'
        );

        $this->assertCount(1, $affectationsSubscribedToEsabora);
        $this->assertInstanceOf(Affectation::class, $affectationsSubscribedToEsabora[0]['affectation']);
        $this->assertEquals('00000000-0000-0000-2023-000000000012', $affectationsSubscribedToEsabora[0]['signalement_uuid']);
    }
}
