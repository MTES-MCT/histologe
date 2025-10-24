<?php

namespace App\Tests\Functional\Repository;

use App\Entity\AutoAffectationRule;
use App\Entity\Territory;
use App\Repository\AutoAffectationRuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AutoAffectationRuleRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testGetAutoAffectationRules(): void
    {
        /** @var AutoAffectationRuleRepository $autoAffectationRuleRepository */
        $autoAffectationRuleRepository = $this->entityManager->getRepository(AutoAffectationRule::class);

        $allAutoAffectationRule = $autoAffectationRuleRepository->getAutoAffectationRules(1, 50, null, null);
        $this->assertCount(11, $allAutoAffectationRule);

        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => '34']);
        $heraultAffectationRule = $autoAffectationRuleRepository->getAutoAffectationRules(1, 50, $territory, null);
        $this->assertCount(5, $heraultAffectationRule);
    }
}
