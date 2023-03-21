<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Signalement;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SignalementTest extends KernelTestCase
{
    public function testSignalementHasNDE(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2023-8']);

        $this->assertTrue($signalement->hasNDE());
    }

    public function testSignalementHasNotNDE(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2023-6']);

        $this->assertFalse($signalement->hasNDE());
    }
}
