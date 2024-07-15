<?php

namespace App\Tests\Functional\Specification\Affectation;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\Affectation\ParcSpecification;
use App\Specification\Context\PartnerSignalementContext;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ParcSpecificationTest extends KernelTestCase
{
    /**
     * @dataProvider provideRulesAndSignalement
     */
    public function testIsSatisfiedBy(?bool $isLogementSocial, string $parcRule, bool $isSatisfied): void
    {
        $partner = new Partner();
        $signalement = new Signalement();
        $signalement->setIsLogementSocial($isLogementSocial);
        $this->assertEquals($isLogementSocial, $signalement->getIsLogementSocial());

        $specification = new ParcSpecification($parcRule);
        $context = new PartnerSignalementContext($partner, $signalement);
        if ($isSatisfied) {
            $this->assertTrue($specification->isSatisfiedBy($context));
        } else {
            $this->assertFalse($specification->isSatisfiedBy($context));
        }
    }

    public function provideRulesAndSignalement(): \Generator
    {
        yield 'all - isLogementSocial' => [true, 'all', true];
        yield 'all - not isLogementSocial' => [false, 'all', true];
        yield 'all - isLogementSocial null' => [null, 'all', true];
        yield 'prive - isLogementSocial' => [true, 'prive', false];
        yield 'prive - not isLogementSocial' => [false, 'prive', true];
        yield 'prive - isLogementSocial null' => [null, 'prive', false];
        yield 'public - isLogementSocial' => [true, 'public', true];
        yield 'public - not isLogementSocial' => [false, 'public', false];
        yield 'public - isLogementSocial null' => [null, 'public', false];
        yield 'non_renseigne - isLogementSocial' => [true, 'non_renseigne', false];
        yield 'non_renseigne - not isLogementSocial' => [false, 'non_renseigne', false];
        yield 'non_renseigne - isLogementSocial null' => [null, 'non_renseigne', true];
    }
}
