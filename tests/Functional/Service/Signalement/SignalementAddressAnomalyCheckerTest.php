<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\Enum\SignalementAddressAnomaly;
use App\Entity\Signalement;
use App\Service\Signalement\SignalementAddressAnomalyChecker;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SignalementAddressAnomalyCheckerTest extends KernelTestCase
{
    private SignalementAddressAnomalyChecker $checker;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->checker = static::getContainer()->get(SignalementAddressAnomalyChecker::class);
    }

    public function testValidNumericInseeFormatRaisesNoError(): void
    {
        $signalement = (new Signalement())->setInseeOccupant('44179')->setCpOccupant('44850');

        $this->assertNotContains(SignalementAddressAnomaly::INVALID_INSEE_FORMAT, $this->checker->getErrors($signalement));
    }

    public function testCorsicaInseeFormatRaisesNoError(): void
    {
        // la Corse utilise "2A"/"2B" comme préfixe de département, ce n'est pas 5 chiffres mais c'est valide
        $signalement = (new Signalement())->setInseeOccupant('2A004')->setCpOccupant('20000');

        $this->assertNotContains(SignalementAddressAnomaly::INVALID_INSEE_FORMAT, $this->checker->getErrors($signalement));
    }

    public function testNonNumericInseeFormatIsInvalid(): void
    {
        // 5 caractères mais pas 5 chiffres (ni la forme Corse valide) : doit être détecté
        $signalement = (new Signalement())->setInseeOccupant('4417X')->setCpOccupant('44850');

        $this->assertContains(SignalementAddressAnomaly::INVALID_INSEE_FORMAT, $this->checker->getErrors($signalement));
    }

    public function testValidCpFormatRaisesNoError(): void
    {
        $signalement = (new Signalement())->setInseeOccupant('44179')->setCpOccupant('44850');

        $this->assertNotContains(SignalementAddressAnomaly::INVALID_CP_FORMAT, $this->checker->getErrors($signalement));
    }

    public function testNonNumericCpFormatIsInvalid(): void
    {
        $signalement = (new Signalement())->setInseeOccupant('44179')->setCpOccupant('4485X');

        $this->assertContains(SignalementAddressAnomaly::INVALID_CP_FORMAT, $this->checker->getErrors($signalement));
    }

    public function testMatchingCpAndInseeRaisesNoInconsistency(): void
    {
        // paire réelle présente dans la table commune (fixtures)
        $signalement = (new Signalement())->setInseeOccupant('2A004')->setCpOccupant('20000');

        $this->assertNotContains(SignalementAddressAnomaly::INCONSISTENT_CP_INSEE, $this->checker->getErrors($signalement));
    }

    public function testMismatchingCpAndInseeIsInconsistent(): void
    {
        // les deux formats sont valides individuellement mais la paire n'existe pas dans la table commune
        $signalement = (new Signalement())->setInseeOccupant('2A004')->setCpOccupant('75002');

        $this->assertContains(SignalementAddressAnomaly::INCONSISTENT_CP_INSEE, $this->checker->getErrors($signalement));
    }

    public function testInconsistentCpInseeIsNotRaisedWhenFormatIsAlreadyInvalid(): void
    {
        // pas d'erreur redondante : si le format est déjà invalide, on ne vérifie pas la cohérence de la paire
        $signalement = (new Signalement())->setInseeOccupant('4417X')->setCpOccupant('75002');

        $this->assertNotContains(SignalementAddressAnomaly::INCONSISTENT_CP_INSEE, $this->checker->getErrors($signalement));
    }
}
