<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Arrete;
use App\Repository\ArreteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ArreteRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ArreteRepository $arreteRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->arreteRepository = $this->entityManager->getRepository(Arrete::class);
    }

    public function testFindByBanIdWithFixtures(): void
    {
        $arretes = $this->arreteRepository->findByBanId('13202_2333_00025');
        $this->assertCount(4, $arretes);
    }
}
