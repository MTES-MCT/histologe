<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Enum\ProfileDeclarant;
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

    public function testGetProfileDeclarant(): void
    {
        $signalement = $this->getSignalement($this->getTerritory('Pas-de-calais', '62'));
        $this->assertEquals(ProfileDeclarant::TIERS_PARTICULIER, $signalement->getProfileDeclarant());
    }

    /** @dataProvider provideProfileDeclarant */
    public function testResolveProfileDeclarant(
        bool $isNotOccupant,
        ProfileDeclarant $profileDeclarant,
        ?string $lienDeclarant = null
    ): void {
        $signalement = $this->getSignalement($this->getTerritory('Pas-de-calais', '62'));
        $signalement->setIsNotOccupant($isNotOccupant);
        $signalement->setLienDeclarantOccupant($lienDeclarant);

        $this->assertEquals($profileDeclarant, $signalement->getProfileDeclarant());
    }

    public function provideProfileDeclarant(): \Generator
    {
        yield 'isOccupant LOCATION' => [false, ProfileDeclarant::LOCATAIRE];
        yield 'isNotOccupant TIERS PROFESSIONNEL' => [true, ProfileDeclarant::TIERS_PRO, 'PROFESSIONNEL'];
        yield 'isNotOccupant TIERS PRO' => [true, ProfileDeclarant::TIERS_PRO, 'pro'];
        yield 'isNotOccupant TIERS assistance sociale' => [true, ProfileDeclarant::TIERS_PRO, 'assistante sociale'];
        yield 'isNotOccupant TIERS curatrice' => [true, ProfileDeclarant::TIERS_PRO, 'curatrice'];
        yield 'isNotOccupant PARTICULIER' => [true, ProfileDeclarant::TIERS_PARTICULIER];
    }
}
