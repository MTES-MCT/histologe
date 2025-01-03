<?php

namespace App\Tests\Functional\Specification\Affectation;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\Affectation\AllocataireSpecification;
use App\Specification\Context\PartnerSignalementContext;
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
        $context = new PartnerSignalementContext($partner, $signalement);
        if ($isSatisfied) {
            $this->assertTrue($specification->isSatisfiedBy($context));
        } else {
            $this->assertFalse($specification->isSatisfiedBy($context));
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
        yield 'all - nsp' => ['nsp', 'all', true];

        yield 'oui - 0' => ['0', 'oui', false];
        yield 'oui - 1' => ['1', 'oui', true];
        yield 'oui - CAF' => ['CAF', 'oui', true];
        yield 'oui - MSA' => ['MSA', 'oui', true];
        yield 'oui - non' => ['non', 'oui', false];
        yield 'oui - oui' => ['oui', 'oui', true];
        yield 'oui - null' => [null, 'oui', false];
        yield 'oui - ' => ['', 'oui', false];
        yield 'oui - nsp' => ['nsp', 'oui', false];

        yield 'non - 0' => ['0', 'non', true];
        yield 'non - 1' => ['1', 'non', false];
        yield 'non - CAF' => ['CAF', 'non', false];
        yield 'non - MSA' => ['MSA', 'non', false];
        yield 'non - non' => ['non', 'non', true];
        yield 'non - oui' => ['oui', 'non', false];
        yield 'non - null' => [null, 'non', false];
        yield 'non - ' => ['', 'non', false];
        yield 'non - nsp' => ['nsp', 'non', false];

        yield 'caf - 0' => ['0', 'caf', false];
        yield 'caf - 1' => ['1', 'caf', false];
        yield 'caf - CAF' => ['CAF', 'caf', true];
        yield 'caf - MSA' => ['MSA', 'caf', false];
        yield 'caf - non' => ['non', 'caf', false];
        yield 'caf - oui' => ['oui', 'caf', false];
        yield 'caf - null' => [null, 'caf', false];
        yield 'caf - ' => ['', 'caf', false];
        yield 'caf - nsp' => ['nsp', 'caf', false];

        yield 'msa - 0' => ['0', 'msa', false];
        yield 'msa - 1' => ['1', 'msa', false];
        yield 'msa - CAF' => ['CAF', 'msa', false];
        yield 'msa - MSA' => ['MSA', 'msa', true];
        yield 'msa - non' => ['non', 'msa', false];
        yield 'msa - oui' => ['oui', 'msa', false];
        yield 'msa - null' => [null, 'msa', false];
        yield 'msa - ' => ['', 'msa', false];
        yield 'msa - nsp' => ['nsp', 'msa', false];

        yield 'nsp - 0' => ['0', 'nsp', false];
        yield 'nsp - 1' => ['1', 'nsp', false];
        yield 'nsp - CAF' => ['CAF', 'nsp', false];
        yield 'nsp - MSA' => ['MSA', 'nsp', false];
        yield 'nsp - non' => ['non', 'nsp', false];
        yield 'nsp - oui' => ['oui', 'nsp', false];
        yield 'nsp - null' => [null, 'nsp', true];
        yield 'nsp - ' => ['', 'nsp', true];
        yield 'nsp - nsp' => ['nsp', 'nsp', true];
    }
}
