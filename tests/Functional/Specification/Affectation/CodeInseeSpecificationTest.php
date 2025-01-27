<?php

namespace App\Tests\Functional\Specification\Affectation;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\Affectation\CodeInseeSpecification;
use App\Specification\Context\PartnerSignalementContext;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CodeInseeSpecificationTest extends KernelTestCase
{
    private const string INSEE_STMARS = '44179';
    private const string INSEE_CELLIER = '44028';

    /**
     * @dataProvider provideRulesAndSignalement
     */
    public function testIsSatisfiedBy(
        ?string $inseeSignalement,
        array $inseePartenaire,
        ?string $inseeToIncludeRule,
        ?array $inseeToExcludeRule,
        bool $isSatisfied,
    ): void {
        $partner = new Partner();
        $partner->setInsee($inseePartenaire);
        $this->assertEquals($inseePartenaire, $partner->getInsee());

        $signalement = new Signalement();
        $signalement->setInseeOccupant($inseeSignalement);
        $this->assertEquals($inseeSignalement, $signalement->getInseeOccupant());

        $specification = new CodeInseeSpecification($inseeToIncludeRule, $inseeToExcludeRule);
        $context = new PartnerSignalementContext($partner, $signalement);
        if ($isSatisfied) {
            $this->assertTrue($specification->isSatisfiedBy($context));
        } else {
            $this->assertFalse($specification->isSatisfiedBy($context));
        }
    }

    public function provideRulesAndSignalement(): \Generator
    {
        yield 'empty - same insee as partner - no exclude' => [self::INSEE_STMARS, [self::INSEE_STMARS], '', null, true];
        yield 'empty - same insee as partner - but excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], '', [self::INSEE_STMARS], false];
        yield 'empty - same insee as partner - another excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], '', [self::INSEE_CELLIER], true];
        yield 'empty - different insee than partner - no exclude' => [self::INSEE_STMARS, [self::INSEE_CELLIER], '', null, true];
        yield 'empty - different insee than partner - but excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], '', [self::INSEE_STMARS], false];
        yield 'empty - different insee than partner - another excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], '', [self::INSEE_CELLIER], true];
        yield 'empty - partner without insee - no exclude' => [self::INSEE_STMARS, [], '', null, true];
        yield 'empty - partner without insee - but excluded' => [self::INSEE_STMARS, [], '', [self::INSEE_STMARS], false];
        yield 'empty - partner without insee - another excluded' => [self::INSEE_STMARS, [], '', [self::INSEE_CELLIER], true];

        yield 'array of insee with this one - same insee as partner - no exclusion' => [self::INSEE_STMARS, [self::INSEE_STMARS], self::INSEE_STMARS, null, true];
        yield 'array of insee with this one - same insee as partner - but excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], self::INSEE_STMARS, [self::INSEE_STMARS], false];
        yield 'array of insee with this one - same insee as partner - another excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], self::INSEE_STMARS, [self::INSEE_CELLIER], true];
        yield 'array of insee with this one - different insee than partner - no exclusion' => [self::INSEE_STMARS, [self::INSEE_CELLIER], self::INSEE_STMARS, null, true];
        yield 'array of insee with this one - different insee than partner - but excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], self::INSEE_STMARS, [self::INSEE_STMARS], false];
        yield 'array of insee with this one - different insee than partner - another excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], self::INSEE_STMARS, [self::INSEE_CELLIER], true];
        yield 'array of insee with this one - partner without insee - no exclusion' => [self::INSEE_STMARS, [], self::INSEE_STMARS, null, true];
        yield 'array of insee with this one - partner without insee - but excluded' => [self::INSEE_STMARS, [], self::INSEE_STMARS, [self::INSEE_STMARS], false];
        yield 'array of insee with this one - partner without insee - another excluded' => [self::INSEE_STMARS, [], self::INSEE_STMARS, [self::INSEE_CELLIER], true];

        yield 'array of insee without this one - same insee as partner - no exclusion' => [self::INSEE_STMARS, [self::INSEE_STMARS], self::INSEE_CELLIER, null, false];
        yield 'array of insee without this one - same insee as partner - but excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], self::INSEE_CELLIER, [self::INSEE_STMARS], false];
        yield 'array of insee without this one - same insee as partner - another excluded' => [self::INSEE_STMARS, [self::INSEE_STMARS], self::INSEE_CELLIER, [self::INSEE_CELLIER], false];
        yield 'array of insee without this one - different insee than partner - no exclusion' => [self::INSEE_STMARS, [self::INSEE_CELLIER], self::INSEE_CELLIER, null, false];
        yield 'array of insee without this one - different insee than partner - but excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], self::INSEE_CELLIER, [self::INSEE_STMARS], false];
        yield 'array of insee without this one - different insee than partner - another excluded' => [self::INSEE_STMARS, [self::INSEE_CELLIER], self::INSEE_CELLIER, [self::INSEE_CELLIER], false];
        yield 'array of insee without this one - partner without insee - no exclusion' => [self::INSEE_STMARS, [], self::INSEE_CELLIER, null, false];
        yield 'array of insee without this one - partner without insee - but excluded' => [self::INSEE_STMARS, [], self::INSEE_CELLIER, [self::INSEE_STMARS], false];
        yield 'array of insee without this one - partner without insee - another excluded' => [self::INSEE_STMARS, [], self::INSEE_CELLIER, [self::INSEE_CELLIER], false];

        // tous les cas ne sont pas testés en cas d'absence d'insee sur le signalement, car ça renvoie toujours false
        yield 'empty - no insee signalement' => [null, [self::INSEE_CELLIER], '', [self::INSEE_CELLIER], false];
        yield 'array of insee - no insee signalement - another excluded' => [null, [], self::INSEE_STMARS, [self::INSEE_CELLIER], false];
    }
}
