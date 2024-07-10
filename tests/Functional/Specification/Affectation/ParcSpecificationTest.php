<?php

namespace App\Tests\Functional\Specification\Affectation;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\Affectation\ParcSpecification;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ParcSpecificationTest extends KernelTestCase
{
    /**
     * @dataProvider provideRulesAndSignalement
     */
    public function testIsSatisfiedBy(bool $isLogementSocial, string $parcRule, bool $isSatisfied): void
    {
        $partner = new Partner();
        $signalement = new Signalement();
        $signalement->setIsLogementSocial($isLogementSocial);
        $this->assertEquals($isLogementSocial, $signalement->getIsLogementSocial());

        $specification = new ParcSpecification($parcRule);
        if ($isSatisfied) {
            $this->assertTrue($specification->isSatisfiedBy(['partner' => $partner, 'signalement' => $signalement]));
        } else {
            $this->assertFalse($specification->isSatisfiedBy(['partner' => $partner, 'signalement' => $signalement]));
        }
    }

    public function provideRulesAndSignalement(): \Generator
    {
        yield 'all - isLogementSocial' => [true, 'all', true];
        yield 'all - not isLogementSocial' => [false, 'all', true];
        yield 'prive - isLogementSocial' => [true, 'prive', false];
        yield 'prive - not isLogementSocial' => [false, 'prive', true];
        yield 'public - isLogementSocial' => [true, 'public', true];
        yield 'public - not isLogementSocial' => [false, 'public', false];
    }
}
