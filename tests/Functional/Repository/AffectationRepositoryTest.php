<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Affectation;
use App\Repository\AffectationRepository;
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
        $affectationsSubscribedToEsabora = $affectationRepository->findAffectationSubscribedToEsabora();
        $this->assertEquals(9, \count($affectationsSubscribedToEsabora));
        /** @var Affectation $affectationSubscribedToEsabora */
        foreach ($affectationsSubscribedToEsabora as $affectationSubscribedToEsabora) {
            $this->assertCount(2, $affectationSubscribedToEsabora->getPartner()->getEsaboraCredential());
        }
    }
}
