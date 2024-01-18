<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Enum\Qualification;
use App\Entity\SignalementQualification;
use App\Tests\FixturesHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SignalementTest extends KernelTestCase
{
    use FixturesHelper;

    public function testSignalementHasRSD(): void
    {
        $signalement = $this->getSignalement($this->getTerritory('Pas-de-calais', '62'));
        $signalement
            ->addSignalementQualification((new SignalementQualification())->setQualification(
                Qualification::NON_DECENCE))
            ->addSignalementQualification((new SignalementQualification())->setQualification(
                Qualification::RSD))
            ->addSignalementQualification((new SignalementQualification())->setQualification(
                Qualification::NON_DECENCE_ENERGETIQUE));

        $this->assertTrue($signalement->hasQualificaton(Qualification::RSD));
    }

    public function testSignalementHasNotRSD(): void
    {
        $signalement = $this->getSignalement($this->getTerritory('Pas-de-calais', '62'));
        $signalement
            ->addSignalementQualification((new SignalementQualification())->setQualification(
                Qualification::NON_DECENCE))
            ->addSignalementQualification((new SignalementQualification())->setQualification(
                Qualification::NON_DECENCE))
            ->addSignalementQualification((new SignalementQualification())->setQualification(
                Qualification::NON_DECENCE_ENERGETIQUE));

        $this->assertFalse($signalement->hasQualificaton(Qualification::RSD));
    }
}
