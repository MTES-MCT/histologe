<?php

namespace App\Tests\Functional\Specification\Affectation;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\Affectation\CodeInseeSpecification;
use App\Specification\Context\PartnerSignalementContext;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CodeInseeSpecificationTest extends KernelTestCase
{
    /**
     * @dataProvider provideRulesAndSignalement
     */
    public function testIsSatisfiedBy(?string $inseeSignalement, array $inseePartenaire, string $inseeToIncludeRule, ?array $inseeToExcludeRule, bool $isSatisfied): void
    {
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
        yield 'all - same insee as partner - no exclude' => ['44179', ['44179'], 'all', null, true];
        yield 'all - same insee as partner - but excluded' => ['44179', ['44179'], 'all', ['44179'], false];
        yield 'all - same insee as partner - another excluded' => ['44179', ['44179'], 'all', ['44028'], true];
        yield 'all - different insee than partner - no exclude' => ['44179', ['44028'], 'all', null, true];
        yield 'all - different insee than partner - but excluded' => ['44179', ['44028'], 'all', ['44179'], false];
        yield 'all - different insee than partner - another excluded' => ['44179', ['44028'], 'all', ['44028'], true];
        yield 'all - partner without insee - no exclude' => ['44179', [], 'all', null, true];
        yield 'all - partner without insee - but excluded' => ['44179', [], 'all', ['44179'], false];
        yield 'all - partner without insee - another excluded' => ['44179', [], 'all', ['44028'], true];

        yield 'partner_list - same insee as partner - no exclusion' => ['44179', ['44179'], 'partner_list', null, true];
        yield 'partner_list - same insee as partner - but excluded' => ['44179', ['44179'], 'partner_list', ['44179'], false];
        yield 'partner_list - same insee as partner - another excluded' => ['44179', ['44179'], 'partner_list', ['44028'], true];
        yield 'partner_list - different insee than partner - no exclusion' => ['44179', ['44028'], 'partner_list', null, false];
        yield 'partner_list - different insee than partner - but excluded' => ['44179', ['44028'], 'partner_list', ['44179'], false];
        yield 'partner_list - different insee than partner - another excluded' => ['44179', ['44028'], 'partner_list', ['44028'], false];
        yield 'partner_list - partner without insee - no exclusion' => ['44179', [], 'partner_list', null, false];
        yield 'partner_list - partner without insee - but excluded' => ['44179', [], 'partner_list', ['44179'], false];
        yield 'partner_list - partner without insee - another excluded' => ['44179', [], 'partner_list', ['44028'], false];

        yield 'array of insee with this one - same insee as partner - no exclusion' => ['44179', ['44179'], '44179', null, true];
        yield 'array of insee with this one - same insee as partner - but excluded' => ['44179', ['44179'], '44179', ['44179'], false];
        yield 'array of insee with this one - same insee as partner - another excluded' => ['44179', ['44179'], '44179', ['44028'], true];
        yield 'array of insee with this one - different insee than partner - no exclusion' => ['44179', ['44028'], '44179', null, true];
        yield 'array of insee with this one - different insee than partner - but excluded' => ['44179', ['44028'], '44179', ['44179'], false];
        yield 'array of insee with this one - different insee than partner - another excluded' => ['44179', ['44028'], '44179', ['44028'], true];
        yield 'array of insee with this one - partner without insee - no exclusion' => ['44179', [], '44179', null, true];
        yield 'array of insee with this one - partner without insee - but excluded' => ['44179', [], '44179', ['44179'], false];
        yield 'array of insee with this one - partner without insee - another excluded' => ['44179', [], '44179', ['44028'], true];

        yield 'array of insee without this one - same insee as partner - no exclusion' => ['44179', ['44179'], '44028', null, false];
        yield 'array of insee without this one - same insee as partner - but excluded' => ['44179', ['44179'], '44028', ['44179'], false];
        yield 'array of insee without this one - same insee as partner - another excluded' => ['44179', ['44179'], '44028', ['44028'], false];
        yield 'array of insee without this one - different insee than partner - no exclusion' => ['44179', ['44028'], '44028', null, false];
        yield 'array of insee without this one - different insee than partner - but excluded' => ['44179', ['44028'], '44028', ['44179'], false];
        yield 'array of insee without this one - different insee than partner - another excluded' => ['44179', ['44028'], '44028', ['44028'], false];
        yield 'array of insee without this one - partner without insee - no exclusion' => ['44179', [], '44028', null, false];
        yield 'array of insee without this one - partner without insee - but excluded' => ['44179', [], '44028', ['44179'], false];
        yield 'array of insee without this one - partner without insee - another excluded' => ['44179', [], '44028', ['44028'], false];

        // tous les cas ne sont pas testés en cas d'absence d'insee sur le signalement, car ça renvoie toujours false
        yield 'all - no insee signalement' => [null, ['44028'], 'all', ['44028'], false];
        yield 'partner_list - no insee signalement - another excluded' => [null, [], 'partner_list', ['44028'], false];
        yield 'array of insee - no insee signalement - another excluded' => [null, [], '44179', ['44028'], false];
    }
}
