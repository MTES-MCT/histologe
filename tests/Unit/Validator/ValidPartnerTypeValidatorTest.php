<?php

namespace App\Tests\Unit\Validator;

use App\Entity\Enum\PartnerType;
use App\Validator\ValidPartnerType;
use App\Validator\ValidPartnerTypeValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ValidPartnerTypeValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new ValidPartnerTypeValidator();
    }

    /**
     * @dataProvider providePartnerType
     */
    public function testPartnerType(PartnerType|string $type, bool $isTypeValid, ?string $message = null): void
    {
        $constraint = new ValidPartnerType();
        $this->validator->validate($type, $constraint);
        if ($isTypeValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($message)
                ->setParameter('{{ value }}', $type)
                ->assertRaised();
        }
    }

    public function providePartnerType(): \Generator
    {
        yield PartnerType::ADIL->value => [PartnerType::ADIL, true];
        yield PartnerType::CCAS->value => [PartnerType::CCAS, true];
        yield PartnerType::CAF_MSA->value => [PartnerType::CAF_MSA, true];
        yield PartnerType::EPCI->value => [PartnerType::EPCI, true];
        yield PartnerType::COMMUNE_SCHS->value => [PartnerType::COMMUNE_SCHS, true];
        yield PartnerType::CONSEIL_DEPARTEMENTAL->value => [PartnerType::CONSEIL_DEPARTEMENTAL, true];
        yield PartnerType::OPERATEUR_VISITES_ET_TRAVAUX->value => [PartnerType::OPERATEUR_VISITES_ET_TRAVAUX, true];
        yield PartnerType::DDT_M->value => [PartnerType::DDT_M, true];
        yield 'error' => ['error', false, 'La valeur "{{ value }}" n\'est pas un PartnerType valide.'];
        yield 'BAILEUR' => ['BAILEUR', false, 'La valeur "{{ value }}" n\'est pas un PartnerType valide.'];
        yield 'all' => ['all', false, 'La valeur "{{ value }}" n\'est pas un PartnerType valide.'];
    }
}
