<?php

namespace App\Tests\Functional\Specification\Affectation;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\Affectation\AllocataireSpecification;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AllocataireSpecificationTest extends KernelTestCase
{
    /**
     * @dataProvider provideRulesAndSignalement
     */
    public function testIsSatisfiedBy(?string $isAllocataire, string $allocataireRule, bool $isSatisfied): void
    {
        $partner = new Partner();
        $signalement = new Signalement();
        $signalement->setIsAllocataire($isAllocataire);
        $this->assertEquals($isAllocataire, $signalement->getIsAllocataire());

        $specification = new AllocataireSpecification($allocataireRule);
        if ($isSatisfied) {
            $this->assertTrue($specification->isSatisfiedBy(['partner' => $partner, 'signalement' => $signalement]));
        } else {
            $this->assertFalse($specification->isSatisfiedBy(['partner' => $partner, 'signalement' => $signalement]));
        }
    }

    public function provideRulesAndSignalement(): \Generator
    {
        yield 'all - 0' => ['0', 'all', true];
        yield 'all - 1' => ['1', 'all', true];
        yield 'all - CAF' => ['CAF', 'all', true];
        yield 'all - MSA' => ['MSA', 'all', true];
        yield 'all - non' => ['non', 'all', true];
        yield 'all - oui' => ['oui', 'all', true];
        yield 'all - null' => [null, 'all', true];
        yield 'all - ' => ['', 'all', true];

        yield 'oui - 0' => ['0', 'oui', false];
        yield 'oui - 1' => ['1', 'oui', true];
        yield 'oui - CAF' => ['CAF', 'oui', true];
        yield 'oui - MSA' => ['MSA', 'oui', true];
        yield 'oui - non' => ['non', 'oui', false];
        yield 'oui - oui' => ['oui', 'oui', true];
        yield 'oui - null' => [null, 'oui', false];
        yield 'oui - ' => ['', 'oui', false];

        yield 'non - 0' => ['0', 'non', true];
        yield 'non - 1' => ['1', 'non', false];
        yield 'non - CAF' => ['CAF', 'non', false];
        yield 'non - MSA' => ['MSA', 'non', false];
        yield 'non - non' => ['non', 'non', true];
        yield 'non - oui' => ['oui', 'non', false];
        yield 'non - null' => [null, 'non', false];
        yield 'non - ' => ['', 'non', false];

        yield 'caf - 0' => ['0', 'caf', false];
        yield 'caf - 1' => ['1', 'caf', false];
        yield 'caf - CAF' => ['CAF', 'caf', true];
        yield 'caf - MSA' => ['MSA', 'caf', false];
        yield 'caf - non' => ['non', 'caf', false];
        yield 'caf - oui' => ['oui', 'caf', false];
        yield 'caf - null' => [null, 'caf', false];
        yield 'caf - ' => ['', 'caf', false];

        yield 'msa - 0' => ['0', 'msa', false];
        yield 'msa - 1' => ['1', 'msa', false];
        yield 'msa - CAF' => ['CAF', 'msa', false];
        yield 'msa - MSA' => ['MSA', 'msa', true];
        yield 'msa - non' => ['non', 'msa', false];
        yield 'msa - oui' => ['oui', 'msa', false];
        yield 'msa - null' => [null, 'msa', false];
        yield 'msa - ' => ['', 'msa', false];
    }
}
