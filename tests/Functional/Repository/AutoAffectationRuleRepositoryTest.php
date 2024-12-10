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

        $allAutoAffectationRule = $autoAffectationRuleRepository->getAutoAffectationRules(null, 1);
        $this->assertCount(9, $allAutoAffectationRule);

        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => '34']);
        $heraultAffectationRule = $autoAffectationRuleRepository->getAutoAffectationRules($territory, 1);
        $this->assertCount(4, $heraultAffectationRule);
    }
}
