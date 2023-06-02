<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Repository\SuiviRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SuiviRepositoryTest extends KernelTestCase
{
    private SuiviRepository $suiviRepository;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->suiviRepository = $this->entityManager->getRepository(Suivi::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testFindFirstSuiviBy(): void
    {
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['reference' => '2023-15']);
        $firstSuivi = $this->suiviRepository->findFirstSuiviBy($signalement, Suivi::TYPE_PARTNER);

        $this->assertStringContainsString('le premier suivi de partenaire 13-01', $firstSuivi->getDescription());
    }
}
