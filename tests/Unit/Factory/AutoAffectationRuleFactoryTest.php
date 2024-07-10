<?php

namespace App\Tests\Unit\Factory;

use App\Entity\AutoAffectationRule;
use App\Entity\Enum\PartnerType;
use App\Entity\Territory;
use App\Factory\AutoAffectationRuleFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AutoAffectationRuleFactoryTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        /* @var ValidatorInterface validator */
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testCreateAutoAffectationRuleOK(): void
    {
        $territory = new Territory();

        $autoAffectationRule = (new AutoAffectationRuleFactory())->createInstanceFrom(
            territory : $territory,
            status : AutoAffectationRule::STATUS_ACTIVE,
            partnerType : PartnerType::CAF_MSA,
            profileDeclarant : 'all',
            inseeToInclude : 'all',
            inseeToExclude : null,
            parc : 'all',
            allocataire : 'all'
        );

        /** @var ConstraintViolationList $errors */
        $errors = $this->validator->validate($autoAffectationRule);
        $this->assertEmpty($errors, (string) $errors);

        $this->assertInstanceOf(AutoAffectationRule::class, $autoAffectationRule);
        $this->assertEquals($autoAffectationRule->getPartnerType(), PartnerType::CAF_MSA);
        $this->assertEmpty($autoAffectationRule->getInseeToExclude());
    }

    public function testCreateAutoAffectationRuleKO(): void
    {
        $territory = new Territory();

        $autoAffectationRule = (new AutoAffectationRuleFactory())->createInstanceFrom(
            territory : $territory,
            status : 'ERROR',
            partnerType : PartnerType::CAF_MSA,
            profileDeclarant : 'ERROR',
            inseeToInclude : 'all',
            inseeToExclude : null,
            parc : 'ERROR',
            allocataire : 'ERROR'
        );

        /** @var ConstraintViolationList $errors */
        $errors = $this->validator->validate($autoAffectationRule);
        $this->assertNotEmpty($errors, (string) $errors);
        $this->assertStringContainsString('Choisissez une option valide: ACTIVE or ARCHIVED', (string) $errors);
        $this->assertStringContainsString('La valeur "ERROR" n\'est pas un profil déclarant ou groupe de profils valide', (string) $errors);
        $this->assertStringContainsString('Choisissez une option valide: all, prive ou public', (string) $errors);
        $this->assertStringContainsString('Choisissez une option valide: all, non, oui, caf ou msa', (string) $errors);
    }
}
