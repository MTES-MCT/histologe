<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Enum\Qualification;
use App\Entity\Signalement;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SignalementQualificationTest extends KernelTestCase
{
    public function testSignalementQualificationIsNDE(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2023-8']);
        $signalementQualification = $signalement->getSignalementQualifications()[0];

        $this->assertEquals(Qualification::NON_DECENCE_ENERGETIQUE, $signalementQualification->getQualification());
    }
}
