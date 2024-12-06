<?php

namespace App\Tests\Functional\Specification\Affectation;

use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Specification\Affectation\ProcedureSuspecteeSpecification;
use App\Specification\Context\PartnerSignalementContext;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProcedureSuspecteeSpecificationTest extends KernelTestCase
{
    /**
     * @dataProvider provideRulesAndSignalement
     */
    public function testIsSatisfiedBy(?array $procedureSuspectees, array $qualificationsSignalement, bool $isSatisfied): void
    {
        $partner = new Partner();
        $signalement = new Signalement();
        foreach ($qualificationsSignalement as $qualificationSignalement) {
            $signalement->addSignalementQualification((new SignalementQualification())->setQualification(
                Qualification::from($qualificationSignalement)
            ));
        }

        $specification = new ProcedureSuspecteeSpecification($procedureSuspectees);
        $context = new PartnerSignalementContext($partner, $signalement);
        if ($isSatisfied) {
            $this->assertTrue($specification->isSatisfiedBy($context));
        } else {
            $this->assertFalse($specification->isSatisfiedBy($context));
        }
    }

    public function provideRulesAndSignalement(): \Generator
    {
        yield 'procedureSuspecte NULL - qualification RSD' => [
            null,
            ['RSD'],
            true,
        ];
        yield '4 procedureSuspecte with RSD - qualification RSD' => [
            [
                Qualification::INSALUBRITE,
                Qualification::NON_DECENCE_ENERGETIQUE,
                Qualification::RSD,
                Qualification::DANGER,
            ],
            ['RSD'],
            true,
        ];
        yield '3 procedureSuspectee without RSD - qualification RSD' => [
            [
                Qualification::INSALUBRITE,
                Qualification::NON_DECENCE_ENERGETIQUE,
                Qualification::DANGER,
            ],
            ['RSD'],
            false,
        ];
        yield '3 procedureSuspectee without RSD with DANGER - qualification RSD and DANGER' => [
            [
                Qualification::INSALUBRITE,
                Qualification::NON_DECENCE_ENERGETIQUE,
                Qualification::DANGER,
            ],
            ['RSD', 'DANGER'],
            true,
        ];
    }
}
