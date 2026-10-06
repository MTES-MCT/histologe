<?php

namespace App\Tests\Functional\Specification\Affectation;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\Affectation\DemandeLogementSocialSpecification;
use App\Specification\Context\PartnerSignalementContext;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DemandeLogementSocialSpecificationTest extends KernelTestCase
{
    #[DataProvider('provideRulesAndSignalement')]
    public function testIsSatisfiedBy(?bool $value, string $rule, bool $isSatisfied): void
    {
        $partner = new Partner();
        $signalement = new Signalement();
        $signalement->setIsRelogement($value);

        $specification = new DemandeLogementSocialSpecification($rule);
        $context = new PartnerSignalementContext($partner, $signalement);
        $this->assertSame($isSatisfied, $specification->isSatisfiedBy($context));
    }

    public static function provideRulesAndSignalement(): \Generator
    {
        yield 'all - oui' => [true, 'all', true];
        yield 'all - non' => [false, 'all', true];
        yield 'all - null' => [null, 'all', true];

        yield 'oui - oui' => [true, 'oui', true];
        yield 'oui - non' => [false, 'oui', false];
        yield 'oui - null' => [null, 'oui', false];

        yield 'non - oui' => [true, 'non', false];
        yield 'non - non' => [false, 'non', true];
        yield 'non - null' => [null, 'non', false];

        yield 'nsp - oui' => [true, 'nsp', false];
        yield 'nsp - non' => [false, 'nsp', false];
        yield 'nsp - null' => [null, 'nsp', true];
    }
}
