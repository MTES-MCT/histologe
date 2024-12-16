<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Entity\Signalement;
use App\Repository\AffectationRepository;
use App\Repository\SignalementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AffectationRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testFindAffectationSubscribedToEsabora(): void
    {
        /** @var AffectationRepository $affectationRepository */
        $affectationRepository = $this->entityManager->getRepository(Affectation::class);
        $affectationsSubscribedToEsabora = $affectationRepository->findAffectationSubscribedToEsabora(PartnerType::ARS);
        $this->assertCount(2, $affectationsSubscribedToEsabora);
        foreach ($affectationsSubscribedToEsabora as $affectationSubscribedToEsabora) {
            $this->assertCount(2, $affectationSubscribedToEsabora->getPartner()->getEsaboraCredential());
        }

        $affectationsSubscribedToEsabora = $affectationRepository->findAffectationSubscribedToEsabora(PartnerType::COMMUNE_SCHS);
        $this->assertCount(1, $affectationsSubscribedToEsabora);
        foreach ($affectationsSubscribedToEsabora as $affectationSubscribedToEsabora) {
            $this->assertCount(2, $affectationSubscribedToEsabora->getPartner()->getEsaboraCredential());
        }

        $affectationsSubscribedToEsabora = $affectationRepository->findAffectationSubscribedToEsabora(
            PartnerType::ARS,
            true,
            '00000000-0000-0000-2023-000000000012'
        );

        $this->assertCount(1, $affectationsSubscribedToEsabora);
    }

    public function testUpdateStatusBySignalement(): void
    {
        /** @var AffectationRepository $affectationRepository */
        $affectationRepository = $this->entityManager->getRepository(Affectation::class);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000004']);
        $affectationRepository->updateStatusBySignalement(Affectation::STATUS_WAIT, $signalement);
        $affectations = $affectationRepository->findBy(['signalement' => $signalement, 'statut' => Affectation::STATUS_WAIT]);
        $this->assertCount(2, $affectations);
    }
}
