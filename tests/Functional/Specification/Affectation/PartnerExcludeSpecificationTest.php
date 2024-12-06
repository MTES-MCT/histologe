<?php

namespace App\Tests\Functional\Specification\Affectation;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\Affectation\PartnerExcludeSpecification;
use App\Specification\Context\PartnerSignalementContext;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PartnerExcludeSpecificationTest extends KernelTestCase
{
    /**
     * @dataProvider provideRulesAndSignalement
     */
    public function testIsSatisfiedBy(?array $partnerExcluded, bool $isSatisfied): void
    {
        $partner = new Partner();
        $partner->setId(1);
        $signalement = new Signalement();

        $specification = new PartnerExcludeSpecification($partnerExcluded);
        $context = new PartnerSignalementContext($partner, $signalement);
        if ($isSatisfied) {
            $this->assertTrue($specification->isSatisfiedBy($context));
        } else {
            $this->assertFalse($specification->isSatisfiedBy($context));
        }
    }

    public function provideRulesAndSignalement(): \Generator
    {
        yield 'null' => [null, true];
        yield 'not in id array' => [[2, 3, 4, 5], true];
        yield 'in id array' => [[1, 2, 3, 4, 5], false];
    }
}
