<?php

namespace App\Tests\Functional\Specification\Affectation;

use App\Entity\Model\SituationFoyer;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Specification\Affectation\DemandeLogementSocialSpecification;
use App\Specification\Context\PartnerSignalementContext;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DemandeLogementSocialSpecificationTest extends KernelTestCase
{
    #[DataProvider('provideRulesAndSignalement')]
    public function testIsSatisfiedBy(?string $value, string $rule, bool $isSatisfied): void
    {
        $partner = new Partner();
        $signalement = new Signalement();
        $signalement->setSituationFoyer(new SituationFoyer(logementSocialDemandeRelogement: $value));

        $specification = new DemandeLogementSocialSpecification($rule);
        $context = new PartnerSignalementContext($partner, $signalement);
        $this->assertSame($isSatisfied, $specification->isSatisfiedBy($context));
    }

    public function testIsSatisfiedByWithoutSituationFoyer(): void
    {
        $context = new PartnerSignalementContext(new Partner(), new Signalement());

        $this->assertTrue((new DemandeLogementSocialSpecification('all'))->isSatisfiedBy($context));
        $this->assertTrue((new DemandeLogementSocialSpecification('nsp'))->isSatisfiedBy($context));
        $this->assertFalse((new DemandeLogementSocialSpecification('oui'))->isSatisfiedBy($context));
        $this->assertFalse((new DemandeLogementSocialSpecification('non'))->isSatisfiedBy($context));
    }

    public static function provideRulesAndSignalement(): \Generator
    {
        yield 'all - oui' => ['oui', 'all', true];
        yield 'all - non' => ['non', 'all', true];
        yield 'all - nsp' => ['nsp', 'all', true];
        yield 'all - null' => [null, 'all', true];
        yield 'all - ' => ['', 'all', true];

        yield 'oui - oui' => ['oui', 'oui', true];
        yield 'oui - non' => ['non', 'oui', false];
        yield 'oui - nsp' => ['nsp', 'oui', false];
        yield 'oui - null' => [null, 'oui', false];
        yield 'oui - ' => ['', 'oui', false];

        yield 'non - oui' => ['oui', 'non', false];
        yield 'non - non' => ['non', 'non', true];
        yield 'non - nsp' => ['nsp', 'non', false];
        yield 'non - null' => [null, 'non', false];
        yield 'non - ' => ['', 'non', false];

        yield 'nsp - oui' => ['oui', 'nsp', false];
        yield 'nsp - non' => ['non', 'nsp', false];
        yield 'nsp - nsp' => ['nsp', 'nsp', true];
        yield 'nsp - null' => [null, 'nsp', true];
        yield 'nsp - ' => ['', 'nsp', true];
    }
}
